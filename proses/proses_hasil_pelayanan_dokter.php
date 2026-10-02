<?php
// ================= PROSES HASIL PELAYANAN DOKTER =================
// Memvalidasi serta menyimpan hasil dan keterangan pelayanan antrean selesai.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('dokter');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !token_csrf_valid($_POST['token_csrf'] ?? null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid.');
    alihkan('dokter/antrean.php');
}

$id_antrean = isset($_POST['id_antrean']) && ctype_digit((string) $_POST['id_antrean'])
    ? (int) $_POST['id_antrean']
    : 0;
$id_dokter = (int) $_SESSION['pengguna']['id_pengguna'];
$hasil_pelayanan = (string) ($_POST['hasil_pelayanan'] ?? '');
$keterangan_pelayanan = trim(strip_tags((string) ($_POST['keterangan_pelayanan'] ?? '')));

if (
    $id_antrean < 1
    || !in_array($hasil_pelayanan, ['pengobatan', 'rujuk'], true)
    || $keterangan_pelayanan === ''
    || strlen($keterangan_pelayanan) > 1000
) {
    simpan_notifikasi('danger', 'Data hasil pelayanan belum valid.');
    alihkan('dokter/antrean.php');
}

try {
    $koneksi_database->beginTransaction();

    // Mengunci antrean dan memastikan status selesai, penugasan poli, serta Dokter pemanggil tetap sesuai.
    $ambil_antrean = $koneksi_database->prepare(
        'SELECT antrean.id_antrean, antrean.hasil_pelayanan, antrean.keterangan_pelayanan
         FROM antrean
         INNER JOIN dokter_poli
             ON dokter_poli.id_poli = antrean.id_poli
             AND dokter_poli.id_pengguna = :id_dokter_penugasan
         WHERE antrean.id_antrean = :id_antrean
           AND antrean.id_dokter = :id_dokter_antrean
           AND antrean.status_antrean = \'selesai\'
         LIMIT 1
         FOR UPDATE'
    );
    $ambil_antrean->execute([
        'id_dokter_penugasan' => $id_dokter,
        'id_dokter_antrean' => $id_dokter,
        'id_antrean' => $id_antrean,
    ]);
    $antrean = $ambil_antrean->fetch();

    if ($antrean === false) {
        throw new RuntimeException('Antrean selesai tidak ditemukan atau bukan wewenang Anda.');
    }
    if ($antrean['hasil_pelayanan'] !== null || $antrean['keterangan_pelayanan'] !== null) {
        throw new RuntimeException('Hasil pelayanan untuk antrean ini sudah diisi.');
    }

    // UPDATE dibatasi pada dua kolom hasil pelayanan tanpa mengubah status maupun data antrean lainnya.
    $simpan_hasil = $koneksi_database->prepare(
        "UPDATE antrean
         SET hasil_pelayanan = :hasil_pelayanan,
             keterangan_pelayanan = :keterangan_pelayanan
         WHERE id_antrean = :id_antrean
           AND id_dokter = :id_dokter
           AND status_antrean = 'selesai'
           AND hasil_pelayanan IS NULL
           AND keterangan_pelayanan IS NULL"
    );
    $simpan_hasil->execute([
        'hasil_pelayanan' => $hasil_pelayanan,
        'keterangan_pelayanan' => $keterangan_pelayanan,
        'id_antrean' => $id_antrean,
        'id_dokter' => $id_dokter,
    ]);
    if ($simpan_hasil->rowCount() !== 1) {
        throw new RuntimeException('Hasil pelayanan sudah diisi atau antrean tidak tersedia.');
    }

    $koneksi_database->commit();
    simpan_notifikasi('success', 'Hasil pelayanan berhasil disimpan.');
} catch (Throwable $kesalahan) {
    if ($koneksi_database->inTransaction()) {
        $koneksi_database->rollBack();
    }

    simpan_notifikasi(
        'danger',
        $kesalahan instanceof RuntimeException
            ? $kesalahan->getMessage()
            : 'Hasil pelayanan gagal disimpan. Silakan coba kembali.'
    );
}

alihkan('dokter/antrean.php');
