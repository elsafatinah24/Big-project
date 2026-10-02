<?php
// ================= PROSES DATA PASIEN =================
// Menangani transaksi tambah, ubah, dan hapus data pasien oleh Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !token_csrf_valid($_POST['token_csrf'] ?? null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid.');
    alihkan('admin/data-pasien.php');
}

$aksi = (string) ($_POST['aksi'] ?? '');
$id_pasien = isset($_POST['id_pasien']) && ctype_digit((string) $_POST['id_pasien'])
    ? (int) $_POST['id_pasien']
    : 0;

/** Memvalidasi data pasien yang dikirim dari form Admin. */
function data_pasien_valid(): array
{
    $nama_pasien = trim((string) ($_POST['nama_pasien'] ?? ''));
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $tanggal_lahir = trim((string) ($_POST['tanggal_lahir'] ?? ''));
    $nik = trim((string) ($_POST['nik'] ?? ''));
    $jenis_pasien = (string) ($_POST['jenis_pasien'] ?? '');
    $jenis_kelamin = (string) ($_POST['jenis_kelamin'] ?? '');
    $alamat = trim((string) ($_POST['alamat'] ?? ''));
    $nomor_telepon = trim((string) ($_POST['nomor_telepon'] ?? ''));
    $kata_sandi = (string) ($_POST['kata_sandi'] ?? '');
    $tanggal_lahir_valid = DateTime::createFromFormat('Y-m-d', $tanggal_lahir);

    if (!preg_match('/^[0-9]{16}$/', $nik)) {
        throw new RuntimeException('NIK harus terdiri dari 16 digit angka.');
    }

    if (
        $nama_pasien === '' || strlen($nama_pasien) > 100
        || $email === false || strlen($email) > 100
        || !in_array($jenis_pasien, ['umum', 'bpjs', 'asuransi_lain'], true)
        || !in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'], true)
        || $alamat === '' || strlen($alamat) > 1000
        || !preg_match('/^[0-9+()\-\s]{8,20}$/', $nomor_telepon)
        || $tanggal_lahir_valid === false
        || $tanggal_lahir_valid->format('Y-m-d') !== $tanggal_lahir
        || $tanggal_lahir_valid > new DateTime('today')
    ) {
        throw new RuntimeException('Data pasien belum valid.');
    }

    return [$nama_pasien, strtolower($email), $tanggal_lahir, $nik, $jenis_pasien, $jenis_kelamin, $alamat, $nomor_telepon, $kata_sandi];
}

try {
    if ($aksi === 'tambah' || $aksi === 'ubah') {
        [$nama_pasien, $email, $tanggal_lahir, $nik, $jenis_pasien, $jenis_kelamin, $alamat, $nomor_telepon, $kata_sandi] = data_pasien_valid();

        if ($kata_sandi !== '' && strlen($kata_sandi) < 8) {
            throw new RuntimeException('Kata sandi harus memiliki minimal 8 karakter.');
        }
        if ($aksi === 'tambah' && $kata_sandi === '') {
            throw new RuntimeException('Kata sandi harus memiliki minimal 8 karakter.');
        }

        $koneksi_database->beginTransaction();

        // NIK mengikuti aturan registrasi pasien: tepat 16 digit dan harus unik.
        $periksa_nik = $koneksi_database->prepare(
            'SELECT id_pasien FROM pasien WHERE nik = :nik AND id_pasien <> :id_pasien LIMIT 1'
        );
        $periksa_nik->execute(['nik' => $nik, 'id_pasien' => $id_pasien]);
        if ($periksa_nik->fetch() !== false) {
            throw new RuntimeException('NIK sudah terdaftar. Silakan periksa kembali NIK pasien.');
        }

        if ($aksi === 'tambah') {
            $simpan_pengguna = $koneksi_database->prepare(
                "INSERT INTO pengguna (nama_pengguna, email, kata_sandi, peran)
                 VALUES (:nama_pengguna, :email, :kata_sandi, 'pasien')"
            );
            $simpan_pengguna->execute([
                'nama_pengguna' => $nama_pasien,
                'email' => $email,
                'kata_sandi' => password_hash($kata_sandi, PASSWORD_DEFAULT),
            ]);

            $simpan_pasien = $koneksi_database->prepare(
                'INSERT INTO pasien (id_pengguna, nik, nama_pasien, tanggal_lahir, jenis_kelamin, alamat, nomor_telepon, jenis_pembayaran)
                 VALUES (:id_pengguna, :nik, :nama_pasien, :tanggal_lahir, :jenis_kelamin, :alamat, :nomor_telepon, :jenis_pembayaran)'
            );
            $simpan_pasien->execute([
                'id_pengguna' => (int) $koneksi_database->lastInsertId(),
                'nik' => $nik,
                'nama_pasien' => $nama_pasien,
                'tanggal_lahir' => $tanggal_lahir,
                'jenis_kelamin' => $jenis_kelamin,
                'alamat' => $alamat,
                'nomor_telepon' => $nomor_telepon,
                'jenis_pembayaran' => $jenis_pasien,
            ]);
            $pesan = 'Data pasien berhasil ditambahkan.';
        } else {
            if ($id_pasien < 1) {
                throw new RuntimeException('Identitas pasien tidak valid.');
            }

            $ambil_akun = $koneksi_database->prepare('SELECT id_pengguna FROM pasien WHERE id_pasien = :id_pasien');
            $ambil_akun->execute(['id_pasien' => $id_pasien]);
            $id_pengguna = $ambil_akun->fetchColumn();

            if ($id_pengguna === false) {
                throw new RuntimeException('Data pasien tidak ditemukan.');
            }

            $sql_pengguna = 'UPDATE pengguna SET nama_pengguna = :nama_pengguna, email = :email'
                . ($kata_sandi !== '' ? ', kata_sandi = :kata_sandi' : '')
                . ' WHERE id_pengguna = :id_pengguna';
            $ubah_pengguna = $koneksi_database->prepare($sql_pengguna);
            $parameter_pengguna = [
                'nama_pengguna' => $nama_pasien,
                'email' => $email,
                'id_pengguna' => (int) $id_pengguna,
            ];

            if ($kata_sandi !== '') {
                $parameter_pengguna['kata_sandi'] = password_hash($kata_sandi, PASSWORD_DEFAULT);
            }

            $ubah_pengguna->execute($parameter_pengguna);

            $ubah_pasien = $koneksi_database->prepare(
                'UPDATE pasien
                 SET nik = :nik, nama_pasien = :nama_pasien, tanggal_lahir = :tanggal_lahir,
                     jenis_kelamin = :jenis_kelamin, alamat = :alamat, nomor_telepon = :nomor_telepon,
                     jenis_pembayaran = :jenis_pembayaran
                 WHERE id_pasien = :id_pasien'
            );
            $ubah_pasien->execute([
                'nik' => $nik,
                'nama_pasien' => $nama_pasien,
                'tanggal_lahir' => $tanggal_lahir,
                'jenis_kelamin' => $jenis_kelamin,
                'alamat' => $alamat,
                'nomor_telepon' => $nomor_telepon,
                'jenis_pembayaran' => $jenis_pasien,
                'id_pasien' => $id_pasien,
            ]);
            $pesan = 'Data pasien berhasil diperbarui.';
        }

        $koneksi_database->commit();
        simpan_notifikasi('success', $pesan);
    } elseif ($aksi === 'hapus') {
        if ($id_pasien < 1) {
            throw new RuntimeException('Identitas pasien tidak valid.');
        }

        $koneksi_database->beginTransaction();
        $ambil_akun = $koneksi_database->prepare('SELECT id_pengguna FROM pasien WHERE id_pasien = :id_pasien');
        $ambil_akun->execute(['id_pasien' => $id_pasien]);
        $id_pengguna = $ambil_akun->fetchColumn();

        if ($id_pengguna === false) {
            throw new RuntimeException('Data pasien tidak ditemukan.');
        }

        $koneksi_database->prepare('DELETE FROM pasien WHERE id_pasien = :id_pasien')->execute(['id_pasien' => $id_pasien]);
        $koneksi_database->prepare('DELETE FROM pengguna WHERE id_pengguna = :id_pengguna')->execute(['id_pengguna' => (int) $id_pengguna]);
        $koneksi_database->commit();
        simpan_notifikasi('success', 'Data pasien dan akun login berhasil dihapus.');
    } else {
        throw new RuntimeException('Aksi data pasien tidak dikenali.');
    }
} catch (Throwable $kesalahan) {
    if ($koneksi_database->inTransaction()) {
        $koneksi_database->rollBack();
    }

    simpan_notifikasi(
        'danger',
        $kesalahan instanceof PDOException && $kesalahan->getCode() === '23000'
            ? 'Email sudah digunakan atau pasien masih terkait antrean.'
            : $kesalahan->getMessage()
    );
}

alihkan('admin/data-pasien.php');
