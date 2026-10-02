<?php
// ================= PROSES PROFIL PASIEN =================
// Memperbarui profil atau kata sandi milik pasien yang sedang login.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('pasien');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !token_csrf_valid($_POST['token_csrf'] ?? null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    alihkan('pasien/profil.php');
}

$aksi = (string) ($_POST['aksi'] ?? '');
$id_pengguna = (int) $_SESSION['pengguna']['id_pengguna'];

// ================= PEMBARUAN DATA PROFIL =================

if ($aksi === 'ubah_profil') {
    $nama_pasien = trim((string) ($_POST['nama_pasien'] ?? ''));
    $nama_pengguna = trim((string) ($_POST['nama_pengguna'] ?? ''));
    $nomor_telepon = trim((string) ($_POST['nomor_telepon'] ?? ''));
    $tanggal_lahir = trim((string) ($_POST['tanggal_lahir'] ?? ''));
    $jenis_kelamin = (string) ($_POST['jenis_kelamin'] ?? '');
    $alamat = trim((string) ($_POST['alamat'] ?? ''));
    $tanggal_lahir_valid = DateTime::createFromFormat('Y-m-d', $tanggal_lahir);

    if (
        $nama_pasien === '' || strlen($nama_pasien) > 100
        || $nama_pengguna === '' || strlen($nama_pengguna) > 100
        || !in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'], true)
        || $alamat === '' || strlen($alamat) > 1000
        || !preg_match('/^[0-9+()\-\s]{8,20}$/', $nomor_telepon)
        || $tanggal_lahir_valid === false
        || $tanggal_lahir_valid->format('Y-m-d') !== $tanggal_lahir
        || $tanggal_lahir_valid > new DateTime('today')
    ) {
        simpan_notifikasi('danger', 'Data profil belum valid. Periksa kembali isian Anda.');
        alihkan('pasien/profil.php');
    }

    try {
        $koneksi_database->beginTransaction();

        // ================= VALIDASI KEPEMILIKAN DAN USERNAME =================

        $ambil_pasien = $koneksi_database->prepare(
            'SELECT id_pasien FROM pasien WHERE id_pengguna = :id_pengguna LIMIT 1 FOR UPDATE'
        );
        $ambil_pasien->execute(['id_pengguna' => $id_pengguna]);
        $id_pasien = $ambil_pasien->fetchColumn();

        if ($id_pasien === false) {
            throw new RuntimeException('Data pasien tidak ditemukan.');
        }

        $cek_username = $koneksi_database->prepare(
            'SELECT id_pengguna
             FROM pengguna
             WHERE nama_pengguna = :nama_pengguna AND id_pengguna <> :id_pengguna
             LIMIT 1
             FOR UPDATE'
        );
        $cek_username->execute([
            'nama_pengguna' => $nama_pengguna,
            'id_pengguna' => $id_pengguna,
        ]);

        if ($cek_username->fetchColumn() !== false) {
            throw new RuntimeException('Username tersebut sudah digunakan.');
        }

        $ubah_pengguna = $koneksi_database->prepare(
            'UPDATE pengguna SET nama_pengguna = :nama_pengguna WHERE id_pengguna = :id_pengguna'
        );
        $ubah_pengguna->execute([
            'nama_pengguna' => $nama_pengguna,
            'id_pengguna' => $id_pengguna,
        ]);

        $ubah_pasien = $koneksi_database->prepare(
            'UPDATE pasien
             SET nama_pasien = :nama_pasien, tanggal_lahir = :tanggal_lahir,
                 jenis_kelamin = :jenis_kelamin, alamat = :alamat, nomor_telepon = :nomor_telepon
             WHERE id_pasien = :id_pasien AND id_pengguna = :id_pengguna'
        );
        $ubah_pasien->execute([
            'nama_pasien' => $nama_pasien,
            'tanggal_lahir' => $tanggal_lahir,
            'jenis_kelamin' => $jenis_kelamin,
            'alamat' => $alamat,
            'nomor_telepon' => $nomor_telepon,
            'id_pasien' => (int) $id_pasien,
            'id_pengguna' => $id_pengguna,
        ]);

        $koneksi_database->commit();
        $_SESSION['pengguna']['nama_pengguna'] = $nama_pengguna;
        simpan_notifikasi('success', 'Profil berhasil diperbarui.');
    } catch (Throwable $kesalahan) {
        if ($koneksi_database->inTransaction()) {
            $koneksi_database->rollBack();
        }

        simpan_notifikasi(
            'danger',
            $kesalahan instanceof RuntimeException
                ? $kesalahan->getMessage()
                : 'Profil gagal diperbarui. Silakan coba kembali.'
        );
    }

    alihkan('pasien/profil.php');
}

// ================= PEMBARUAN KATA SANDI =================

if ($aksi === 'ubah_kata_sandi') {
    $kata_sandi_lama = (string) ($_POST['kata_sandi_lama'] ?? '');
    $kata_sandi_baru = (string) ($_POST['kata_sandi_baru'] ?? '');
    $konfirmasi_kata_sandi = (string) ($_POST['konfirmasi_kata_sandi'] ?? '');

    if (
        $kata_sandi_lama === '' || strlen($kata_sandi_baru) < 8
        || $kata_sandi_baru !== $konfirmasi_kata_sandi
    ) {
        simpan_notifikasi('danger', 'Kata sandi baru minimal 8 karakter dan harus sama dengan konfirmasi.');
        alihkan('pasien/profil.php');
    }

    try {
        $koneksi_database->beginTransaction();

        $ambil_akun = $koneksi_database->prepare(
            "SELECT kata_sandi FROM pengguna WHERE id_pengguna = :id_pengguna AND peran = 'pasien' LIMIT 1 FOR UPDATE"
        );
        $ambil_akun->execute(['id_pengguna' => $id_pengguna]);
        $akun = $ambil_akun->fetch();

        if ($akun === false || !password_verify($kata_sandi_lama, $akun['kata_sandi'])) {
            throw new RuntimeException('Kata sandi lama tidak sesuai.');
        }

        $ubah_kata_sandi = $koneksi_database->prepare(
            'UPDATE pengguna SET kata_sandi = :kata_sandi WHERE id_pengguna = :id_pengguna'
        );
        $ubah_kata_sandi->execute([
            'kata_sandi' => password_hash($kata_sandi_baru, PASSWORD_DEFAULT),
            'id_pengguna' => $id_pengguna,
        ]);

        $koneksi_database->commit();
        simpan_notifikasi('success', 'Kata sandi berhasil diperbarui.');
    } catch (Throwable $kesalahan) {
        if ($koneksi_database->inTransaction()) {
            $koneksi_database->rollBack();
        }

        simpan_notifikasi(
            'danger',
            $kesalahan instanceof RuntimeException
                ? $kesalahan->getMessage()
                : 'Kata sandi gagal diperbarui. Silakan coba kembali.'
        );
    }

    alihkan('pasien/profil.php');
}

simpan_notifikasi('danger', 'Aksi profil tidak dikenali.');
alihkan('pasien/profil.php');
