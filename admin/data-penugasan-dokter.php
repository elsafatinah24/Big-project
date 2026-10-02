<?php
// ================= MODUL PENUGASAN DOKTER =================
// Menampilkan serta menyediakan form penugasan dokter ke poli untuk Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

// ================= PENGAMBILAN DATA PENUGASAN =================

$ambil_dokter = $koneksi_database->prepare(
    "SELECT id_pengguna, nama_pengguna
     FROM pengguna
     WHERE peran = 'dokter'
     ORDER BY nama_pengguna ASC"
);
$ambil_dokter->execute();
$daftar_dokter = $ambil_dokter->fetchAll();

$ambil_poli = $koneksi_database->prepare(
    'SELECT id_poli, kode_poli, nama_poli, status_aktif
     FROM poli
     ORDER BY nama_poli ASC'
);
$ambil_poli->execute();
$daftar_poli = $ambil_poli->fetchAll();

$cari = trim((string) ($_GET['cari'] ?? ''));
if (strlen($cari) > 100) {
    $cari = substr($cari, 0, 100);
}

$id_poli_filter = isset($_GET['id_poli']) && ctype_digit((string) $_GET['id_poli'])
    ? (int) $_GET['id_poli']
    : 0;
$id_poli_tersedia = array_map(static fn (array $poli): int => (int) $poli['id_poli'], $daftar_poli);
if ($id_poli_filter > 0 && !in_array($id_poli_filter, $id_poli_tersedia, true)) {
    $id_poli_filter = 0;
}

$kondisi_penugasan = [];
$parameter_penugasan = [];
if ($cari !== '') {
    $kondisi_penugasan[] = 'pengguna.nama_pengguna LIKE :cari';
    $parameter_penugasan['cari'] = '%' . $cari . '%';
}
if ($id_poli_filter > 0) {
    $kondisi_penugasan[] = 'poli.id_poli = :id_poli';
    $parameter_penugasan['id_poli'] = $id_poli_filter;
}
$where_penugasan = $kondisi_penugasan === [] ? '' : ' WHERE ' . implode(' AND ', $kondisi_penugasan);

$ambil_penugasan = $koneksi_database->prepare(
    'SELECT dokter_poli.id_dokter_poli, pengguna.nama_pengguna, poli.kode_poli,
            poli.nama_poli, poli.status_aktif, dokter_poli.dibuat_pada
     FROM dokter_poli
     INNER JOIN pengguna ON pengguna.id_pengguna = dokter_poli.id_pengguna
     INNER JOIN poli ON poli.id_poli = dokter_poli.id_poli' . $where_penugasan . '
     ORDER BY pengguna.nama_pengguna ASC, poli.nama_poli ASC'
);
$ambil_penugasan->execute($parameter_penugasan);
$daftar_penugasan = $ambil_penugasan->fetchAll();

$judul_halaman = 'Penugasan Dokter';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'penugasan-dokter';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
        <div class="mb-4">
            <p class="teks-label mb-1">Manajemen Dokter</p>
            <h1 class="h3 mb-1">Penugasan Dokter ke Poli</h1>
            <p class="text-secondary mb-0">Atur poli tempat setiap dokter bertugas.</p>
        </div>

        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <?php if ($daftar_dokter === []): ?>
            <div class="alert alert-info" role="alert">Belum ada akun dokter yang tersedia.</div>
        <?php else: ?>
            <section class="card kartu-dashboard mb-4">
                <div class="card-body p-3 p-md-4">
                    <h2 class="judul-kartu mb-3">Tambah Penugasan</h2>
                    <?php if ($daftar_poli === []): ?>
                        <div class="alert alert-warning mb-0" role="alert">Belum ada poli yang tersedia untuk ditugaskan.</div>
                    <?php else: ?>
                        <form class="row g-3 needs-validation" method="post" action="<?= aman_html(url_aplikasi('proses/proses_penugasan_dokter.php')); ?>" novalidate>
                            <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                            <input type="hidden" name="aksi" value="tambah">
                            <div class="col-md-6">
                                <label class="form-label" for="cari_dokter">Dokter</label>
                                <div class="position-relative">
                                    <input class="form-control" id="cari_dokter" type="search" placeholder="Pilih atau cari dokter..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="hasil_dokter" aria-expanded="false" required>
                                    <input id="id_pengguna" type="hidden" name="id_pengguna" value="">
                                    <div id="hasil_dokter" class="list-group position-absolute w-100 shadow-sm d-none" role="listbox" style="z-index: 1050; max-height: 240px; overflow-y: auto;"></div>
                                    <div id="pesan_dokter" class="invalid-feedback">Pilih dokter dari hasil pencarian.</div>
                                </div>
                                <div class="form-text">Klik untuk melihat daftar dokter, atau ketik nama dokter lalu pilih hasil yang sesuai.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="id_poli">Poli</label>
                                <select class="form-select" id="id_poli" name="id_poli" required>
                                    <option value="">Pilih poli</option>
                                    <?php foreach ($daftar_poli as $poli): ?>
                                        <option value="<?= (int) $poli['id_poli']; ?>"><?= aman_html($poli['kode_poli'] . ' - ' . $poli['nama_poli']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12"><button class="btn btn-success" type="submit">Simpan Penugasan</button></div>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="card kartu-dashboard">
            <div class="card-body p-3 p-md-4 border-bottom">
                <form class="row g-3 align-items-end" method="get" action="<?= aman_html(url_aplikasi('admin/data-penugasan-dokter.php')); ?>">
                    <div class="col-md-6">
                        <label class="form-label" for="cari">Cari Dokter</label>
                        <input class="form-control" id="cari" type="search" name="cari" value="<?= aman_html($cari); ?>" placeholder="Ketik nama dokter...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="id_poli_filter">Filter Poli</label>
                        <select class="form-select" id="id_poli_filter" name="id_poli">
                            <option value="">Semua Poli</option>
                            <?php foreach ($daftar_poli as $poli): ?>
                                <option value="<?= (int) $poli['id_poli']; ?>" <?= $id_poli_filter === (int) $poli['id_poli'] ? 'selected' : ''; ?>><?= aman_html($poli['kode_poli'] . ' - ' . $poli['nama_poli']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-success flex-fill" type="submit">Cari</button>
                        <a class="btn btn-outline-secondary flex-fill" href="<?= aman_html(url_aplikasi('admin/data-penugasan-dokter.php')); ?>">Reset</a>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="d-flex justify-content-between align-items-center p-3 p-md-4 border-bottom">
                    <h2 class="judul-kartu mb-0">Daftar Penugasan</h2>
                    <span class="small text-secondary"><?= count($daftar_penugasan); ?> data</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-success">
                            <tr><th class="ps-3">Dokter</th><th>Poli</th><th>Dibuat Pada</th><th class="text-end pe-3">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($daftar_penugasan === []): ?>
                                <tr><td colspan="4" class="text-center text-secondary py-5"><?= $cari !== '' || $id_poli_filter > 0 ? 'Penugasan dokter tidak ditemukan.' : 'Belum ada penugasan dokter ke poli.'; ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($daftar_penugasan as $penugasan): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= aman_html($penugasan['nama_pengguna']); ?></td>
                                        <td>
                                            <div><?= aman_html($penugasan['kode_poli'] . ' - ' . $penugasan['nama_poli']); ?></div>
                                            <span class="badge text-bg-<?= $penugasan['status_aktif'] === 'aktif' ? 'success' : 'secondary'; ?> mt-1"><?= $penugasan['status_aktif'] === 'aktif' ? 'Aktif' : 'Nonaktif'; ?></span>
                                        </td>
                                        <td><?= aman_html(date('d-m-Y H:i', strtotime($penugasan['dibuat_pada']))); ?></td>
                                        <td class="text-end pe-3">
                                            <form id="formulir-hapus-penugasan-<?= (int) $penugasan['id_dokter_poli']; ?>" class="mb-0" method="post" action="<?= aman_html(url_aplikasi('proses/proses_penugasan_dokter.php')); ?>">
                                                <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                                                <input type="hidden" name="aksi" value="hapus">
                                                <input type="hidden" name="id_dokter_poli" value="<?= (int) $penugasan['id_dokter_poli']; ?>">
                                                <button class="btn btn-sm btn-outline-danger tombol-hapus-penugasan" type="button" data-bs-toggle="modal" data-bs-target="#modalHapusPenugasan" data-id-formulir="formulir-hapus-penugasan-<?= (int) $penugasan['id_dokter_poli']; ?>">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- ================= MODAL KONFIRMASI HAPUS PENUGASAN ================= -->
<div class="modal fade" id="modalHapusPenugasan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0">
        <div class="modal-header bg-success text-white"><h2 class="fs-5 modal-title">Konfirmasi Hapus Penugasan</h2><button class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body">Apakah Anda yakin ingin menghapus penugasan dokter dari poli ini?</div>
        <div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-success" id="konfirmasiHapusPenugasan">Ya, Hapus</button></div>
    </div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ================= PENCARIAN DOKTER SISI KLIEN =================
    // Memanfaatkan data dokter yang telah diambil halaman ini tanpa request tambahan.
    const dokter = <?= json_encode($daftar_dokter, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const formulirTambah = document.querySelector('.needs-validation');
    const inputCariDokter = document.getElementById('cari_dokter');
    const inputIdDokter = document.getElementById('id_pengguna');
    const hasilDokter = document.getElementById('hasil_dokter');
    const pesanDokter = document.getElementById('pesan_dokter');

    if (formulirTambah !== null) {
        function tutupHasilDokter() {
        hasilDokter.replaceChildren();
        hasilDokter.classList.add('d-none');
        inputCariDokter.setAttribute('aria-expanded', 'false');
        }

        function pilihDokter(dataDokter) {
        inputCariDokter.value = dataDokter.nama_pengguna;
        inputIdDokter.value = dataDokter.id_pengguna;
        inputCariDokter.setCustomValidity('');
        inputCariDokter.classList.remove('is-invalid');
        pesanDokter.classList.remove('d-block');
        tutupHasilDokter();
        }

        function cariDokter() {
        const kataKunci = inputCariDokter.value.trim().toLocaleLowerCase();
        return dokter.filter(function (dataDokter) {
            return dataDokter.nama_pengguna.toLocaleLowerCase().includes(kataKunci);
        }).slice(0, 10);
        }

        function tampilkanHasilDokter() {
        const cocok = cariDokter();
        tutupHasilDokter();

        if (cocok.length === 0) {
            const kosong = document.createElement('div');
            kosong.className = 'list-group-item text-secondary small';
            kosong.textContent = 'Dokter tidak ditemukan.';
            hasilDokter.appendChild(kosong);
        } else {
            cocok.forEach(function (dataDokter) {
                const pilihan = document.createElement('button');
                pilihan.type = 'button';
                pilihan.className = 'list-group-item list-group-item-action';
                pilihan.setAttribute('role', 'option');
                pilihan.textContent = dataDokter.nama_pengguna;
                // pointerdown berjalan sebelum input kehilangan fokus, sehingga hasil
                // tetap dapat dipilih sebelum handler blur menutup dropdown.
                pilihan.addEventListener('pointerdown', function (event) {
                    event.preventDefault();
                    pilihDokter(dataDokter);
                });
                hasilDokter.appendChild(pilihan);
            });
        }

        hasilDokter.classList.remove('d-none');
        inputCariDokter.setAttribute('aria-expanded', 'true');
        }

        inputCariDokter.addEventListener('input', function () {
        inputIdDokter.value = '';
        inputCariDokter.setCustomValidity('Pilih dokter dari hasil pencarian.');
        tampilkanHasilDokter();
        });
        inputCariDokter.addEventListener('focus', tampilkanHasilDokter);
        inputCariDokter.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            const cocok = cariDokter();
            if (cocok.length > 0) {
                event.preventDefault();
                pilihDokter(cocok[0]);
            }
        } else if (event.key === 'Escape') {
            tutupHasilDokter();
        }
        });
        inputCariDokter.addEventListener('blur', function () {
        window.setTimeout(tutupHasilDokter, 150);
        });
        formulirTambah.addEventListener('submit', function (event) {
        if (inputIdDokter.value === '') {
            inputCariDokter.setCustomValidity('Pilih dokter dari hasil pencarian.');
            inputCariDokter.classList.add('is-invalid');
            pesanDokter.classList.add('d-block');
        } else {
            inputCariDokter.setCustomValidity('');
        }

        if (!formulirTambah.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        formulirTambah.classList.add('was-validated');
        });
    }

    let idFormulir = '';
    document.querySelectorAll('.tombol-hapus-penugasan').forEach(function (tombol) {
        tombol.addEventListener('click', function () { idFormulir = this.dataset.idFormulir; });
    });
    document.getElementById('konfirmasiHapusPenugasan').addEventListener('click', function () {
        const formulir = document.getElementById(idFormulir);
        if (formulir) { formulir.submit(); }
    });
});
</script>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
