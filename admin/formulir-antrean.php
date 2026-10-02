<?php
// ================= FORMULIR DATA ANTREAN =================
// Form tambah dan edit antrean untuk Admin.
declare(strict_types=1);
require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';
wajib_peran('admin');

$id_antrean = isset($_GET['id_antrean']) && ctype_digit((string) $_GET['id_antrean']) ? (int) $_GET['id_antrean'] : 0;
$mode_edit = $id_antrean > 0;
$antrean = ['id_pasien'=>'', 'id_poli'=>'', 'tanggal_antrean'=>date('Y-m-d'), 'status_antrean'=>'menunggu'];
if ($mode_edit) {
    $ambil = $koneksi_database->prepare('SELECT id_pasien,id_poli,tanggal_antrean,status_antrean FROM antrean WHERE id_antrean=:id LIMIT 1');
    $ambil->execute(['id'=>$id_antrean]); $antrean=$ambil->fetch();
    if ($antrean === false) { simpan_notifikasi('danger','Data antrean tidak ditemukan.'); alihkan('admin/data-antrean.php'); }
}

// ================= DATA PILIHAN FORM =================
$daftar_pasien=$koneksi_database->query('SELECT id_pasien,nama_pasien FROM pasien ORDER BY nama_pasien')->fetchAll();
$daftar_poli=$koneksi_database->query('SELECT id_poli,kode_poli,nama_poli,status_aktif FROM poli ORDER BY nama_poli')->fetchAll();
$nama_pasien_terpilih = '';
foreach ($daftar_pasien as $pasien) {
    if ((int) $antrean['id_pasien'] === (int) $pasien['id_pasien']) {
        $nama_pasien_terpilih = $pasien['nama_pasien'];
        break;
    }
}
$judul_halaman=$mode_edit?'Edit Antrean':'Tambah Antrean'; $stylesheet_tambahan='assets/css/gaya-dashboard.css'; $menu_aktif='antrean';
require_once __DIR__ . '/../includes/kepala.php'; require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard"><?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
<main class="flex-grow-1 p-3 p-md-4 p-xl-5"><div class="mb-4"><p class="teks-label mb-1">Manajemen Data</p><h1 class="h3 mb-1"><?= $judul_halaman; ?></h1><p class="text-secondary mb-0">Nomor antrean dibuat otomatis berdasarkan poli dan tanggal.</p></div><?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
<section class="card kartu-dashboard" style="max-width:720px"><div class="card-body p-4"><form method="post" action="<?= aman_html(url_aplikasi('proses/proses_antrean.php')); ?>" class="needs-validation" novalidate><input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>"><input type="hidden" name="aksi" value="<?= $mode_edit?'ubah':'tambah'; ?>"><?php if($mode_edit): ?><input type="hidden" name="id_antrean" value="<?= $id_antrean; ?>"><?php endif; ?>
<div class="mb-3 position-relative"><label class="form-label" for="cari_pasien">Pasien</label><input class="form-control" id="cari_pasien" type="search" value="<?= aman_html($nama_pasien_terpilih); ?>" placeholder="Ketik nama pasien..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="hasil_pasien" aria-expanded="false" required><input id="id_pasien" type="hidden" name="id_pasien" value="<?= (int) $antrean['id_pasien']; ?>"><div id="hasil_pasien" class="list-group position-absolute w-100 shadow-sm d-none" role="listbox" style="z-index: 1050; max-height: 240px; overflow-y: auto;"></div><div id="pesan_pasien" class="invalid-feedback">Pilih pasien dari hasil pencarian.</div><div class="form-text">Ketik minimal satu huruf nama pasien, lalu pilih hasil yang sesuai.</div></div>
<div class="mb-3"><label class="form-label" for="id_poli">Poli</label><select class="form-select" id="id_poli" name="id_poli" required><option value="">Pilih poli</option><?php foreach($daftar_poli as $poli): ?><option value="<?= (int)$poli['id_poli']; ?>" <?= (int)$antrean['id_poli']===(int)$poli['id_poli']?'selected':''; ?> <?= !$mode_edit && $poli['status_aktif']!=='aktif'?'disabled':''; ?>><?= aman_html($poli['kode_poli'].' - '.$poli['nama_poli']); ?></option><?php endforeach; ?></select><div class="invalid-feedback">Poli wajib dipilih.</div></div>
<div class="mb-3"><label class="form-label" for="tanggal_antrean">Tanggal Antrean</label><input class="form-control" id="tanggal_antrean" type="date" name="tanggal_antrean" value="<?= aman_html($antrean['tanggal_antrean']); ?>" required><div class="invalid-feedback">Tanggal antrean wajib diisi.</div></div>
<div class="mb-4"><label class="form-label" for="status_antrean">Status Antrean</label><select class="form-select" id="status_antrean" name="status_antrean" required><?php foreach(['menunggu'=>'Menunggu','dipanggil'=>'Dipanggil','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan'] as $nilai=>$label): ?><option value="<?= $nilai; ?>" <?= $antrean['status_antrean']===$nilai?'selected':''; ?>><?= $label; ?></option><?php endforeach; ?></select></div>
<button class="btn btn-success" type="submit"><?= $mode_edit?'Simpan Perubahan':'Simpan Antrean'; ?></button> <a class="btn btn-outline-secondary" href="<?= aman_html(url_aplikasi('admin/data-antrean.php')); ?>">Batal</a></form></div></section></main></div>
<script>
// ================= PENCARIAN PASIEN SISI KLIEN =================
// Menampilkan hasil dari data pasien yang telah tersedia tanpa request tambahan ke server.
document.addEventListener('DOMContentLoaded', function () {
    const pasien = <?= json_encode($daftar_pasien, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const formulir = document.querySelector('.needs-validation');
    const inputCari = document.getElementById('cari_pasien');
    const inputId = document.getElementById('id_pasien');
    const hasil = document.getElementById('hasil_pasien');
    const pesan = document.getElementById('pesan_pasien');

    function tutupHasil() {
        hasil.replaceChildren();
        hasil.classList.add('d-none');
        inputCari.setAttribute('aria-expanded', 'false');
    }

    function pilihPasien(dataPasien) {
        inputCari.value = dataPasien.nama_pasien;
        inputId.value = dataPasien.id_pasien;
        inputCari.setCustomValidity('');
        inputCari.classList.remove('is-invalid');
        pesan.classList.remove('d-block');
        tutupHasil();
    }

    function tampilkanHasil() {
        const kataKunci = inputCari.value.trim().toLocaleLowerCase();
        tutupHasil();

        if (kataKunci === '') {
            return;
        }

        const cocok = pasien.filter(function (dataPasien) {
            return dataPasien.nama_pasien.toLocaleLowerCase().includes(kataKunci);
        }).slice(0, 10);

        if (cocok.length === 0) {
            const kosong = document.createElement('div');
            kosong.className = 'list-group-item text-secondary small';
            kosong.textContent = 'Pasien tidak ditemukan.';
            hasil.appendChild(kosong);
        } else {
            cocok.forEach(function (dataPasien) {
                const pilihan = document.createElement('button');
                pilihan.type = 'button';
                pilihan.className = 'list-group-item list-group-item-action';
                pilihan.setAttribute('role', 'option');
                pilihan.textContent = dataPasien.nama_pasien;
                pilihan.addEventListener('click', function () {
                    pilihPasien(dataPasien);
                });
                hasil.appendChild(pilihan);
            });
        }

        hasil.classList.remove('d-none');
        inputCari.setAttribute('aria-expanded', 'true');
    }

    inputCari.addEventListener('input', function () {
        inputId.value = '';
        inputCari.setCustomValidity('Pilih pasien dari hasil pencarian.');
        tampilkanHasil();
    });
    inputCari.addEventListener('focus', tampilkanHasil);
    inputCari.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            tutupHasil();
        }
    });
    inputCari.addEventListener('blur', function () {
        window.setTimeout(tutupHasil, 150);
    });

    formulir.addEventListener('submit', function (event) {
        if (inputId.value === '') {
            inputCari.setCustomValidity('Pilih pasien dari hasil pencarian.');
            inputCari.classList.add('is-invalid');
            pesan.classList.add('d-block');
        } else {
            inputCari.setCustomValidity('');
        }

        if (!formulir.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        formulir.classList.add('was-validated');
    });
});
</script>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
