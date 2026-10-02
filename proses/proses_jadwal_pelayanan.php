<?php
// ================= PROSES JADWAL PELAYANAN =================
// Menangani validasi serta CRUD jadwal pelayanan oleh Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !token_csrf_valid($_POST['token_csrf'] ?? null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid.');
    alihkan('admin/data-jadwal-pelayanan.php');
}

$aksi = (string) ($_POST['aksi'] ?? '');
$id_jadwal_pelayanan = isset($_POST['id_jadwal_pelayanan']) && ctype_digit((string) $_POST['id_jadwal_pelayanan'])
    ? (int) $_POST['id_jadwal_pelayanan']
    : 0;

/** Memvalidasi data jadwal pelayanan yang dikirim dari form. */
function data_jadwal_valid(): array
{
    $hari = (string) ($_POST['hari'] ?? '');
    $jam_buka = trim((string) ($_POST['jam_buka'] ?? ''));
    $jam_tutup = trim((string) ($_POST['jam_tutup'] ?? ''));
    $keterangan = trim((string) ($_POST['keterangan'] ?? ''));
    $status_aktif = (string) ($_POST['status_aktif'] ?? '');
    $jam_buka_valid = DateTime::createFromFormat('H:i', $jam_buka);
    $jam_tutup_valid = DateTime::createFromFormat('H:i', $jam_tutup);

    if (
        !in_array($hari, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'], true)
        || $jam_buka_valid === false || $jam_buka_valid->format('H:i') !== $jam_buka
        || $jam_tutup_valid === false || $jam_tutup_valid->format('H:i') !== $jam_tutup
        || $jam_buka >= $jam_tutup
        || strlen($keterangan) > 1000
        || !in_array($status_aktif, ['aktif', 'tidak_aktif'], true)
    ) {
        throw new RuntimeException('Data jadwal pelayanan belum valid.');
    }

    return [$hari, $jam_buka . ':00', $jam_tutup . ':00', $keterangan === '' ? null : $keterangan, $status_aktif];
}

try {
    if ($aksi === 'tambah' || $aksi === 'ubah') {
        [$hari, $jam_buka, $jam_tutup, $keterangan, $status_aktif] = data_jadwal_valid();

        if ($aksi === 'tambah') {
            $simpan_jadwal = $koneksi_database->prepare(
                'INSERT INTO jadwal_pelayanan (hari, jam_buka, jam_tutup, keterangan, status_aktif)
                 VALUES (:hari, :jam_buka, :jam_tutup, :keterangan, :status_aktif)'
            );
            $simpan_jadwal->execute([
                'hari' => $hari,
                'jam_buka' => $jam_buka,
                'jam_tutup' => $jam_tutup,
                'keterangan' => $keterangan,
                'status_aktif' => $status_aktif,
            ]);
            simpan_notifikasi('success', 'Jadwal pelayanan berhasil ditambahkan.');
        } elseif ($id_jadwal_pelayanan > 0) {
            $ubah_jadwal = $koneksi_database->prepare(
                'UPDATE jadwal_pelayanan
                 SET hari = :hari, jam_buka = :jam_buka, jam_tutup = :jam_tutup,
                     keterangan = :keterangan, status_aktif = :status_aktif
                 WHERE id_jadwal_pelayanan = :id_jadwal_pelayanan'
            );
            $ubah_jadwal->execute([
                'hari' => $hari,
                'jam_buka' => $jam_buka,
                'jam_tutup' => $jam_tutup,
                'keterangan' => $keterangan,
                'status_aktif' => $status_aktif,
                'id_jadwal_pelayanan' => $id_jadwal_pelayanan,
            ]);
            simpan_notifikasi($ubah_jadwal->rowCount() > 0 ? 'success' : 'warning', $ubah_jadwal->rowCount() > 0 ? 'Jadwal pelayanan berhasil diperbarui.' : 'Tidak ada perubahan data atau jadwal tidak ditemukan.');
        } else {
            throw new RuntimeException('Identitas jadwal pelayanan tidak valid.');
        }
    } elseif ($aksi === 'hapus') {
        if ($id_jadwal_pelayanan < 1) {
            throw new RuntimeException('Identitas jadwal pelayanan tidak valid.');
        }

        $hapus_jadwal = $koneksi_database->prepare('DELETE FROM jadwal_pelayanan WHERE id_jadwal_pelayanan = :id_jadwal_pelayanan');
        $hapus_jadwal->execute(['id_jadwal_pelayanan' => $id_jadwal_pelayanan]);
        simpan_notifikasi($hapus_jadwal->rowCount() === 1 ? 'success' : 'danger', $hapus_jadwal->rowCount() === 1 ? 'Jadwal pelayanan berhasil dihapus.' : 'Jadwal pelayanan tidak ditemukan.');
    } else {
        throw new RuntimeException('Aksi jadwal pelayanan tidak dikenali.');
    }
} catch (Throwable $kesalahan) {
    simpan_notifikasi(
        'danger',
        $kesalahan instanceof RuntimeException
            ? $kesalahan->getMessage()
            : 'Jadwal pelayanan gagal diproses. Silakan coba kembali.'
    );
}

alihkan('admin/data-jadwal-pelayanan.php');
