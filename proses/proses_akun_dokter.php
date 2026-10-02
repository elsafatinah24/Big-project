<?php
// ================= PROSES AKUN DOKTER =================
// Memvalidasi, membuat, dan menghapus akun Dokter oleh Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !token_csrf_valid($_POST['token_csrf'] ?? null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid.');
    alihkan('admin/data-akun-dokter.php');
}

$aksi = (string) ($_POST['aksi'] ?? '');
$id_pengguna = isset($_POST['id_pengguna']) && ctype_digit((string) $_POST['id_pengguna'])
    ? (int) $_POST['id_pengguna']
    : 0;

/** Memvalidasi data akun Dokter yang dikirim dari form Admin. */
function data_akun_dokter_valid(bool $kata_sandi_wajib): array
{
    $nama_pengguna = trim((string) ($_POST['nama_pengguna'] ?? ''));
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $kata_sandi = (string) ($_POST['kata_sandi'] ?? '');

    if ($nama_pengguna === '' || strlen($nama_pengguna) > 100) {
        throw new RuntimeException('Data akun dokter belum valid.');
    }
    if ($email === false || strlen($email) > 100) {
        throw new RuntimeException('Masukkan alamat email yang valid.');
    }
    if (
        ($kata_sandi_wajib && strlen($kata_sandi) < 8)
        || (!$kata_sandi_wajib && $kata_sandi !== '' && strlen($kata_sandi) < 8)
    ) {
        throw new RuntimeException('Kata sandi harus memiliki minimal 8 karakter.');
    }

    return [$nama_pengguna, strtolower($email), $kata_sandi];
}

try {
    if ($aksi === 'tambah') {
        [$nama_pengguna, $email, $kata_sandi] = data_akun_dokter_valid(true);

        $periksa_nama_dokter = $koneksi_database->prepare(
            "SELECT id_pengguna FROM pengguna
             WHERE peran = 'dokter' AND LOWER(nama_pengguna) = LOWER(:nama_pengguna)
             LIMIT 1"
        );
        $periksa_nama_dokter->execute(['nama_pengguna' => $nama_pengguna]);
        if ($periksa_nama_dokter->fetch() !== false) {
            throw new RuntimeException('Nama dokter sudah digunakan. Silakan gunakan nama dokter lain.');
        }

        $periksa_email = $koneksi_database->prepare('SELECT id_pengguna FROM pengguna WHERE email = :email LIMIT 1');
        $periksa_email->execute(['email' => $email]);
        if ($periksa_email->fetch() !== false) {
            throw new RuntimeException('Email sudah digunakan. Gunakan email yang lain.');
        }

        // Role ditetapkan langsung agar form ini hanya dapat membuat akun Dokter.
        $simpan_dokter = $koneksi_database->prepare(
            "INSERT INTO pengguna (nama_pengguna, email, kata_sandi, peran)
             VALUES (:nama_pengguna, :email, :kata_sandi, 'dokter')"
        );
        $simpan_dokter->execute([
            'nama_pengguna' => $nama_pengguna,
            'email' => $email,
            'kata_sandi' => password_hash($kata_sandi, PASSWORD_DEFAULT),
        ]);
        simpan_notifikasi('success', 'Akun dokter berhasil ditambahkan.');
    } elseif ($aksi === 'ubah') {
        if ($id_pengguna < 1) {
            throw new RuntimeException('Identitas akun dokter tidak valid.');
        }

        [$nama_pengguna, $email, $kata_sandi] = data_akun_dokter_valid(false);
        $koneksi_database->beginTransaction();

        $periksa_dokter = $koneksi_database->prepare(
            "SELECT id_pengguna FROM pengguna
             WHERE id_pengguna = :id_pengguna AND peran = 'dokter'
             LIMIT 1 FOR UPDATE"
        );
        $periksa_dokter->execute(['id_pengguna' => $id_pengguna]);
        if ($periksa_dokter->fetch() === false) {
            throw new RuntimeException('Akun dokter tidak ditemukan.');
        }

        $periksa_nama_dokter = $koneksi_database->prepare(
            "SELECT id_pengguna FROM pengguna
             WHERE peran = 'dokter'
               AND LOWER(nama_pengguna) = LOWER(:nama_pengguna)
               AND id_pengguna <> :id_pengguna
             LIMIT 1"
        );
        $periksa_nama_dokter->execute([
            'nama_pengguna' => $nama_pengguna,
            'id_pengguna' => $id_pengguna,
        ]);
        if ($periksa_nama_dokter->fetch() !== false) {
            throw new RuntimeException('Nama dokter sudah digunakan. Silakan gunakan nama dokter lain.');
        }

        $periksa_email = $koneksi_database->prepare(
            'SELECT id_pengguna FROM pengguna
             WHERE email = :email AND id_pengguna <> :id_pengguna
             LIMIT 1'
        );
        $periksa_email->execute(['email' => $email, 'id_pengguna' => $id_pengguna]);
        if ($periksa_email->fetch() !== false) {
            throw new RuntimeException('Email sudah digunakan. Gunakan email yang lain.');
        }

        $sql_ubah = 'UPDATE pengguna SET nama_pengguna = :nama_pengguna, email = :email'
            . ($kata_sandi !== '' ? ', kata_sandi = :kata_sandi' : '')
            . " WHERE id_pengguna = :id_pengguna AND peran = 'dokter'";
        $ubah_dokter = $koneksi_database->prepare($sql_ubah);
        $parameter_ubah = [
            'nama_pengguna' => $nama_pengguna,
            'email' => $email,
            'id_pengguna' => $id_pengguna,
        ];
        if ($kata_sandi !== '') {
            $parameter_ubah['kata_sandi'] = password_hash($kata_sandi, PASSWORD_DEFAULT);
        }
        $ubah_dokter->execute($parameter_ubah);

        $koneksi_database->commit();
        simpan_notifikasi('success', 'Akun dokter berhasil diperbarui.');
    } elseif ($aksi === 'hapus') {
        if ($id_pengguna < 1) {
            throw new RuntimeException('Identitas akun dokter tidak valid.');
        }

        $koneksi_database->beginTransaction();

        // Mengunci dan memeriksa role agar ID pengguna selain Dokter tidak dapat dihapus lewat proses ini.
        $periksa_dokter = $koneksi_database->prepare(
            "SELECT id_pengguna FROM pengguna
             WHERE id_pengguna = :id_pengguna AND peran = 'dokter'
             LIMIT 1 FOR UPDATE"
        );
        $periksa_dokter->execute(['id_pengguna' => $id_pengguna]);
        if ($periksa_dokter->fetch() === false) {
            throw new RuntimeException('Akun dokter tidak ditemukan.');
        }

        $hitung_penugasan = $koneksi_database->prepare(
            'SELECT COUNT(*) FROM dokter_poli WHERE id_pengguna = :id_pengguna'
        );
        $hitung_penugasan->execute(['id_pengguna' => $id_pengguna]);

        $hitung_antrean = $koneksi_database->prepare(
            'SELECT COUNT(*) FROM antrean WHERE id_dokter = :id_pengguna'
        );
        $hitung_antrean->execute(['id_pengguna' => $id_pengguna]);

        if ((int) $hitung_penugasan->fetchColumn() > 0 || (int) $hitung_antrean->fetchColumn() > 0) {
            throw new RuntimeException('Akun dokter tidak dapat dihapus karena masih memiliki penugasan poli atau terkait antrean.');
        }

        // Penghapusan hanya dilakukan pada akun Dokter yang sudah lolos pemeriksaan relasi.
        $hapus_dokter = $koneksi_database->prepare(
            "DELETE FROM pengguna WHERE id_pengguna = :id_pengguna AND peran = 'dokter'"
        );
        $hapus_dokter->execute(['id_pengguna' => $id_pengguna]);
        if ($hapus_dokter->rowCount() !== 1) {
            throw new RuntimeException('Akun dokter tidak ditemukan.');
        }

        $koneksi_database->commit();
        simpan_notifikasi('success', 'Akun dokter berhasil dihapus.');
    } else {
        throw new RuntimeException('Aksi akun dokter tidak dikenali.');
    }
} catch (Throwable $kesalahan) {
    if ($koneksi_database->inTransaction()) {
        $koneksi_database->rollBack();
    }

    simpan_notifikasi(
        'danger',
        $kesalahan instanceof RuntimeException
            ? $kesalahan->getMessage()
            : 'Akun dokter gagal diproses. Silakan coba kembali.'
    );
}

alihkan('admin/data-akun-dokter.php');
