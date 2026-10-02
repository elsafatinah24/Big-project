<?php
// ================= PROSES PENGAMBILAN ANTREAN =================
// Memvalidasi data kunjungan lalu membuat nomor antrean pasien untuk hari ini atau besok.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('pasien');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !token_csrf_valid($_POST['token_csrf'] ?? null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    alihkan('pasien/ambil-antrean.php');
}

// ================= VALIDASI PILIHAN POLI =================

$id_poli = isset($_POST['id_poli']) && ctype_digit((string) $_POST['id_poli'])
    ? (int) $_POST['id_poli']
    : 0;

if ($id_poli < 1) {
    simpan_notifikasi('danger', 'Silakan pilih poli yang tersedia.');
    alihkan('pasien/ambil-antrean.php');
}

$id_pengguna = (int) $_SESSION['pengguna']['id_pengguna'];
$metode_pembayaran = (string) ($_POST['metode_pembayaran'] ?? '');
$tanggal_antrean = trim((string) ($_POST['tanggal_antrean'] ?? ''));
$keluhan = trim(strip_tags((string) ($_POST['keluhan'] ?? '')));
$tanggal_hari_ini = date('Y-m-d');
$tanggal_besok = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');

// ================= VALIDASI DATA KUNJUNGAN =================

if (!in_array($metode_pembayaran, ['umum', 'bpjs', 'asuransi_lain'], true)) {
    simpan_notifikasi('danger', 'Silakan pilih metode pembayaran yang valid.');
    alihkan('pasien/ambil-antrean.php');
}

if (!in_array($tanggal_antrean, [$tanggal_hari_ini, $tanggal_besok], true)) {
    simpan_notifikasi('danger', 'Tanggal antrean hanya dapat dipilih untuk hari ini atau besok.');
    alihkan('pasien/ambil-antrean.php');
}

if (strlen($keluhan) > 1000) {
    simpan_notifikasi('danger', 'Keluhan maksimal 1.000 karakter.');
    alihkan('pasien/ambil-antrean.php');
}

try {
    $koneksi_database->beginTransaction();

    // ================= VALIDASI PASIEN DAN POLI =================

    $ambil_pasien = $koneksi_database->prepare(
        'SELECT id_pasien FROM pasien WHERE id_pengguna = :id_pengguna LIMIT 1 FOR UPDATE'
    );
    $ambil_pasien->execute(['id_pengguna' => $id_pengguna]);
    $id_pasien = $ambil_pasien->fetchColumn();

    if ($id_pasien === false) {
        throw new RuntimeException('Data pasien tidak ditemukan.');
    }

    $ambil_poli = $koneksi_database->prepare(
        "SELECT id_poli FROM poli WHERE id_poli = :id_poli AND status_aktif = 'aktif' LIMIT 1 FOR UPDATE"
    );
    $ambil_poli->execute(['id_poli' => $id_poli]);

    if ($ambil_poli->fetchColumn() === false) {
        throw new RuntimeException('Poli tidak tersedia atau sudah tidak aktif.');
    }

    // ================= CEK ANTREAN AKTIF =================

    $cek_antrean_aktif = $koneksi_database->prepare(
        "SELECT id_antrean
         FROM antrean
         WHERE id_pasien = :id_pasien
           AND id_poli = :id_poli
           AND tanggal_antrean = :tanggal_antrean
           AND status_antrean IN ('menunggu', 'dipanggil')
         LIMIT 1
         FOR UPDATE"
    );
    $cek_antrean_aktif->execute([
        'id_pasien' => (int) $id_pasien,
        'id_poli' => $id_poli,
        'tanggal_antrean' => $tanggal_antrean,
    ]);

    if ($cek_antrean_aktif->fetchColumn() !== false) {
        throw new RuntimeException('Anda sudah memiliki antrean aktif pada poli dan tanggal yang dipilih.');
    }

    // ================= PENOMORAN DAN PENYIMPANAN ANTREAN =================

    $ambil_nomor = $koneksi_database->prepare(
        'SELECT COALESCE(MAX(nomor_antrean), 0) + 1
         FROM antrean
         WHERE id_poli = :id_poli AND tanggal_antrean = :tanggal_antrean
         FOR UPDATE'
    );
    $ambil_nomor->execute([
        'id_poli' => $id_poli,
        'tanggal_antrean' => $tanggal_antrean,
    ]);
    $nomor_antrean = (int) $ambil_nomor->fetchColumn();

    $simpan_antrean = $koneksi_database->prepare(
        "INSERT INTO antrean (id_pasien, id_poli, nomor_antrean, tanggal_antrean, status_antrean, metode_pembayaran, keluhan)
         VALUES (:id_pasien, :id_poli, :nomor_antrean, :tanggal_antrean, 'menunggu', :metode_pembayaran, :keluhan)"
    );
    $simpan_antrean->execute([
        'id_pasien' => (int) $id_pasien,
        'id_poli' => $id_poli,
        'nomor_antrean' => $nomor_antrean,
        'tanggal_antrean' => $tanggal_antrean,
        'metode_pembayaran' => $metode_pembayaran,
        'keluhan' => $keluhan === '' ? null : $keluhan,
    ]);

    $id_antrean = (int) $koneksi_database->lastInsertId();
    $koneksi_database->commit();

    simpan_notifikasi('success', 'Antrean berhasil dibuat. Berikut nomor antrean Anda.');
    alihkan('pasien/hasil-antrean.php?id_antrean=' . $id_antrean);
} catch (Throwable $kesalahan) {
    if ($koneksi_database->inTransaction()) {
        $koneksi_database->rollBack();
    }

    simpan_notifikasi(
        'danger',
        $kesalahan instanceof RuntimeException
            ? $kesalahan->getMessage()
            : 'Antrean gagal dibuat. Silakan coba kembali.'
    );
    alihkan('pasien/ambil-antrean.php');
}
