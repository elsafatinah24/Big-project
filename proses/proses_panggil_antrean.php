<?php
// ================= PROSES PANGGIL ANTREAN =================
// Memvalidasi dan menyimpan perubahan status antrean hari ini oleh Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !token_csrf_valid($_POST['token_csrf'] ?? null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid.');
    alihkan('admin/panggil-antrean.php');
}

$id_antrean = isset($_POST['id_antrean']) && ctype_digit((string) $_POST['id_antrean'])
    ? (int) $_POST['id_antrean']
    : 0;
$status_tujuan = (string) ($_POST['status_tujuan'] ?? '');

if ($id_antrean < 1 || !in_array($status_tujuan, ['dipanggil', 'selesai', 'dibatalkan'], true)) {
    simpan_notifikasi('danger', 'Data perubahan status tidak valid.');
    alihkan('admin/panggil-antrean.php');
}

$transisi_diizinkan = [
    'menunggu' => ['dipanggil', 'dibatalkan'],
    'dipanggil' => ['selesai', 'dibatalkan'],
];

try {
    $koneksi_database->beginTransaction();

    // ================= VALIDASI STATUS ANTREAN =================

    $ambil_antrean = $koneksi_database->prepare(
        'SELECT status_antrean, tanggal_antrean, waktu_dipanggil, waktu_selesai
         FROM antrean
         WHERE id_antrean = :id_antrean
         LIMIT 1
         FOR UPDATE'
    );
    $ambil_antrean->execute(['id_antrean' => $id_antrean]);
    $antrean = $ambil_antrean->fetch();

    if ($antrean === false || $antrean['tanggal_antrean'] !== date('Y-m-d')) {
        throw new RuntimeException('Antrean hari ini tidak ditemukan.');
    }

    $status_saat_ini = $antrean['status_antrean'];

    if (!isset($transisi_diizinkan[$status_saat_ini]) || !in_array($status_tujuan, $transisi_diizinkan[$status_saat_ini], true)) {
        throw new RuntimeException('Perubahan status antrean tidak diizinkan.');
    }

    // ================= PEMBARUAN STATUS DAN WAKTU =================

    $waktu_dipanggil = $status_tujuan === 'dipanggil' && $antrean['waktu_dipanggil'] === null
        ? date('Y-m-d H:i:s')
        : $antrean['waktu_dipanggil'];
    $waktu_selesai = $status_tujuan === 'selesai' && $antrean['waktu_selesai'] === null
        ? date('Y-m-d H:i:s')
        : $antrean['waktu_selesai'];

    $ubah_antrean = $koneksi_database->prepare(
        'UPDATE antrean
         SET status_antrean = :status_antrean, waktu_dipanggil = :waktu_dipanggil, waktu_selesai = :waktu_selesai
         WHERE id_antrean = :id_antrean'
    );
    $ubah_antrean->execute([
        'status_antrean' => $status_tujuan,
        'waktu_dipanggil' => $waktu_dipanggil,
        'waktu_selesai' => $waktu_selesai,
        'id_antrean' => $id_antrean,
    ]);

    $koneksi_database->commit();

    $pesan = [
        'dipanggil' => 'Antrean berhasil dipanggil.',
        'selesai' => 'Antrean berhasil diselesaikan.',
        'dibatalkan' => 'Antrean berhasil dibatalkan.',
    ];
    simpan_notifikasi('success', $pesan[$status_tujuan]);
} catch (Throwable $kesalahan) {
    if ($koneksi_database->inTransaction()) {
        $koneksi_database->rollBack();
    }

    simpan_notifikasi(
        'danger',
        $kesalahan instanceof RuntimeException
            ? $kesalahan->getMessage()
            : 'Status antrean gagal diperbarui. Silakan coba kembali.'
    );
}

alihkan('admin/panggil-antrean.php');
