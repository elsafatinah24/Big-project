<?php
// ================= PROSES PANGGIL ANTREAN DOKTER =================
// Memvalidasi dan memanggil antrean hari ini dari poli yang ditugaskan kepada Dokter.

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

if ($id_antrean < 1) {
    simpan_notifikasi('danger', 'Identitas antrean tidak valid.');
    alihkan('dokter/antrean.php');
}

try {
    $koneksi_database->beginTransaction();

    // Mengunci antrean sekaligus membuktikan bahwa polinya ditugaskan kepada Dokter login.
    $ambil_antrean = $koneksi_database->prepare(
        'SELECT antrean.id_antrean, antrean.id_poli, antrean.status_antrean,
                antrean.tanggal_antrean, antrean.waktu_dipanggil
         FROM antrean
         INNER JOIN dokter_poli
             ON dokter_poli.id_poli = antrean.id_poli
             AND dokter_poli.id_pengguna = :id_dokter
         WHERE antrean.id_antrean = :id_antrean
         LIMIT 1
         FOR UPDATE'
    );
    $ambil_antrean->execute([
        'id_dokter' => $id_dokter,
        'id_antrean' => $id_antrean,
    ]);
    $antrean = $ambil_antrean->fetch();

    if ($antrean === false) {
        throw new RuntimeException('Antrean tidak ditemukan atau bukan dari poli penugasan Anda.');
    }
    if ($antrean['tanggal_antrean'] !== date('Y-m-d')) {
        throw new RuntimeException('Hanya antrean hari ini yang dapat dipanggil.');
    }
    if ($antrean['status_antrean'] !== 'menunggu') {
        throw new RuntimeException('Antrean sudah dipanggil atau tidak tersedia.');
    }

    // Mengunci poli agar dua request Dokter pada poli yang sama diproses secara berurutan.
    $kunci_poli = $koneksi_database->prepare('SELECT id_poli FROM poli WHERE id_poli = :id_poli LIMIT 1 FOR UPDATE');
    $kunci_poli->execute(['id_poli' => (int) $antrean['id_poli']]);

    // Mencegah pemanggilan lebih dari satu antrean aktif pada poli yang sama secara bersamaan.
    $periksa_panggilan_aktif = $koneksi_database->prepare(
        "SELECT id_antrean FROM antrean
         WHERE id_poli = :id_poli
           AND tanggal_antrean = :tanggal_antrean
           AND status_antrean = 'dipanggil'
         LIMIT 1
         FOR UPDATE"
    );
    $periksa_panggilan_aktif->execute([
        'id_poli' => (int) $antrean['id_poli'],
        'tanggal_antrean' => date('Y-m-d'),
    ]);
    if ($periksa_panggilan_aktif->fetch() !== false) {
        throw new RuntimeException('Masih ada antrean yang sedang dipanggil pada poli ini.');
    }

    // Kondisi status pada UPDATE menjadi pengaman akhir terhadap request ganda.
    $ubah_antrean = $koneksi_database->prepare(
        "UPDATE antrean
         SET status_antrean = 'dipanggil',
             waktu_dipanggil = COALESCE(waktu_dipanggil, :waktu_dipanggil),
             id_dokter = :id_dokter
         WHERE id_antrean = :id_antrean
           AND tanggal_antrean = :tanggal_antrean
           AND status_antrean = 'menunggu'"
    );
    $ubah_antrean->execute([
        'waktu_dipanggil' => date('Y-m-d H:i:s'),
        'id_dokter' => $id_dokter,
        'id_antrean' => $id_antrean,
        'tanggal_antrean' => date('Y-m-d'),
    ]);
    if ($ubah_antrean->rowCount() !== 1) {
        throw new RuntimeException('Antrean sudah dipanggil atau tidak tersedia.');
    }

    $koneksi_database->commit();
    simpan_notifikasi('success', 'Antrean berhasil dipanggil.');
} catch (Throwable $kesalahan) {
    if ($koneksi_database->inTransaction()) {
        $koneksi_database->rollBack();
    }

    simpan_notifikasi(
        'danger',
        $kesalahan instanceof RuntimeException
            ? $kesalahan->getMessage()
            : 'Antrean gagal dipanggil. Silakan coba kembali.'
    );
}

alihkan('dokter/antrean.php');
