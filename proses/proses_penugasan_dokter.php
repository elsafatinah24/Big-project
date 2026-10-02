<?php
// ================= PROSES PENUGASAN DOKTER =================
// Memvalidasi serta menyimpan atau menghapus penugasan dokter ke poli oleh Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !token_csrf_valid($_POST['token_csrf'] ?? null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid.');
    alihkan('admin/data-penugasan-dokter.php');
}

$aksi = (string) ($_POST['aksi'] ?? '');
$id_pengguna = isset($_POST['id_pengguna']) && ctype_digit((string) $_POST['id_pengguna'])
    ? (int) $_POST['id_pengguna']
    : 0;
$id_poli = isset($_POST['id_poli']) && ctype_digit((string) $_POST['id_poli'])
    ? (int) $_POST['id_poli']
    : 0;
$id_dokter_poli = isset($_POST['id_dokter_poli']) && ctype_digit((string) $_POST['id_dokter_poli'])
    ? (int) $_POST['id_dokter_poli']
    : 0;

try {
    if ($aksi === 'tambah') {
        if ($id_pengguna < 1 || $id_poli < 1) {
            throw new RuntimeException('Dokter dan poli wajib dipilih.');
        }

        $koneksi_database->beginTransaction();

        // Memastikan ID pengguna benar-benar mengacu pada akun berperan dokter.
        $periksa_dokter = $koneksi_database->prepare(
            "SELECT id_pengguna FROM pengguna WHERE id_pengguna = :id_pengguna AND peran = 'dokter' LIMIT 1 FOR UPDATE"
        );
        $periksa_dokter->execute(['id_pengguna' => $id_pengguna]);
        if ($periksa_dokter->fetch() === false) {
            throw new RuntimeException('Dokter yang dipilih tidak valid.');
        }

        // Memastikan poli yang dipilih tersedia pada data poli.
        $periksa_poli = $koneksi_database->prepare('SELECT id_poli FROM poli WHERE id_poli = :id_poli LIMIT 1 FOR UPDATE');
        $periksa_poli->execute(['id_poli' => $id_poli]);
        if ($periksa_poli->fetch() === false) {
            throw new RuntimeException('Poli yang dipilih tidak valid.');
        }

        $periksa_duplikat = $koneksi_database->prepare(
            'SELECT id_dokter_poli FROM dokter_poli
             WHERE id_pengguna = :id_pengguna AND id_poli = :id_poli
             LIMIT 1 FOR UPDATE'
        );
        $periksa_duplikat->execute(['id_pengguna' => $id_pengguna, 'id_poli' => $id_poli]);
        if ($periksa_duplikat->fetch() !== false) {
            throw new RuntimeException('Dokter sudah ditugaskan pada poli tersebut.');
        }

        $simpan_penugasan = $koneksi_database->prepare(
            'INSERT INTO dokter_poli (id_pengguna, id_poli) VALUES (:id_pengguna, :id_poli)'
        );
        $simpan_penugasan->execute(['id_pengguna' => $id_pengguna, 'id_poli' => $id_poli]);
        $koneksi_database->commit();
        simpan_notifikasi('success', 'Penugasan dokter berhasil ditambahkan.');
    } elseif ($aksi === 'hapus') {
        if ($id_dokter_poli < 1) {
            throw new RuntimeException('Identitas penugasan dokter tidak valid.');
        }

        // Penghapusan hanya menargetkan satu baris relasi dokter_poli.
        $hapus_penugasan = $koneksi_database->prepare(
            'DELETE FROM dokter_poli WHERE id_dokter_poli = :id_dokter_poli'
        );
        $hapus_penugasan->execute(['id_dokter_poli' => $id_dokter_poli]);
        simpan_notifikasi(
            $hapus_penugasan->rowCount() === 1 ? 'success' : 'danger',
            $hapus_penugasan->rowCount() === 1 ? 'Penugasan dokter berhasil dihapus.' : 'Penugasan dokter tidak ditemukan.'
        );
    } else {
        throw new RuntimeException('Aksi penugasan dokter tidak dikenali.');
    }
} catch (Throwable $kesalahan) {
    if ($koneksi_database->inTransaction()) {
        $koneksi_database->rollBack();
    }

    simpan_notifikasi(
        'danger',
        $kesalahan instanceof RuntimeException
            ? $kesalahan->getMessage()
            : 'Penugasan dokter gagal diproses. Silakan coba kembali.'
    );
}

alihkan('admin/data-penugasan-dokter.php');
