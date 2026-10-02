<?php
// ================= PROSES SELESAI ANTREAN DOKTER =================
// Memvalidasi dan menyelesaikan antrean hari ini yang dipanggil oleh Dokter terkait.

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

    // Mengunci antrean dan memastikan poli serta Dokter pemanggil sesuai dengan session saat ini.
    $ambil_antrean = $koneksi_database->prepare(
        'SELECT antrean.id_antrean, antrean.status_antrean, antrean.tanggal_antrean
         FROM antrean
         INNER JOIN dokter_poli
             ON dokter_poli.id_poli = antrean.id_poli
             AND dokter_poli.id_pengguna = :id_dokter_penugasan
         WHERE antrean.id_antrean = :id_antrean
           AND antrean.id_dokter = :id_dokter_antrean
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
        throw new RuntimeException('Antrean tidak ditemukan atau bukan antrean yang Anda panggil.');
    }
    if ($antrean['tanggal_antrean'] !== date('Y-m-d')) {
        throw new RuntimeException('Hanya antrean hari ini yang dapat diselesaikan.');
    }
    if ($antrean['status_antrean'] !== 'dipanggil') {
        throw new RuntimeException('Antrean sudah selesai atau tidak tersedia.');
    }

    // Kondisi status pada UPDATE menjadi pengaman akhir terhadap request penyelesaian ganda.
    $ubah_antrean = $koneksi_database->prepare(
        "UPDATE antrean
         SET status_antrean = 'selesai',
             waktu_selesai = COALESCE(waktu_selesai, :waktu_selesai)
         WHERE id_antrean = :id_antrean
           AND id_dokter = :id_dokter
           AND tanggal_antrean = :tanggal_antrean
           AND status_antrean = 'dipanggil'"
    );
    $ubah_antrean->execute([
        'waktu_selesai' => date('Y-m-d H:i:s'),
        'id_antrean' => $id_antrean,
        'id_dokter' => $id_dokter,
        'tanggal_antrean' => date('Y-m-d'),
    ]);
    if ($ubah_antrean->rowCount() !== 1) {
        throw new RuntimeException('Antrean sudah selesai atau tidak tersedia.');
    }

    $koneksi_database->commit();
    simpan_notifikasi('success', 'Antrean berhasil diselesaikan.');
} catch (Throwable $kesalahan) {
    if ($koneksi_database->inTransaction()) {
        $koneksi_database->rollBack();
    }

    simpan_notifikasi(
        'danger',
        $kesalahan instanceof RuntimeException
            ? $kesalahan->getMessage()
            : 'Antrean gagal diselesaikan. Silakan coba kembali.'
    );
}

alihkan('dokter/antrean.php');
