<?php
// ================= PROSES REGISTRASI PASIEN =================
// Memvalidasi data lalu menyimpan akun pengguna dan identitas pasien dalam satu transaksi.

declare(strict_types=1);

require_once __DIR__ . '/../konfigurasi/koneksi_database.php';
require_once __DIR__ . '/../includes/pembantu.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alihkan('auth/registrasi.php');
}

$token_csrf = $_POST['token_csrf'] ?? null;
$nama_pasien = trim((string) ($_POST['nama_pasien'] ?? ''));
$tanggal_lahir = trim((string) ($_POST['tanggal_lahir'] ?? ''));
$nik = trim((string) ($_POST['nik'] ?? ''));
$jenis_kelamin = (string) ($_POST['jenis_kelamin'] ?? '');
$jenis_pembayaran = (string) ($_POST['jenis_pasien'] ?? '');
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$nomor_telepon = trim((string) ($_POST['nomor_telepon'] ?? ''));
$alamat = trim((string) ($_POST['alamat'] ?? ''));
$kata_sandi = (string) ($_POST['kata_sandi'] ?? '');
$konfirmasi_kata_sandi = (string) ($_POST['konfirmasi_kata_sandi'] ?? '');

if (!token_csrf_valid(is_string($token_csrf) ? $token_csrf : null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    alihkan('auth/registrasi.php');
}

// ================= VALIDASI NIK DAN JAMINAN =================

if ($nik === '') {
    simpan_notifikasi('danger', 'NIK wajib diisi.');
    alihkan('auth/registrasi.php');
}

if (!preg_match('/^[0-9]{16}$/', $nik)) {
    simpan_notifikasi('danger', 'NIK harus terdiri dari 16 digit angka.');
    alihkan('auth/registrasi.php');
}

if (!in_array($jenis_pembayaran, ['umum', 'bpjs', 'asuransi_lain'], true)) {
    simpan_notifikasi('danger', 'Jenis pembayaran wajib dipilih dengan nilai yang valid.');
    alihkan('auth/registrasi.php');
}

$tanggal_lahir_valid = DateTime::createFromFormat('Y-m-d', $tanggal_lahir);
$tanggal_hari_ini = new DateTime('today');

if (
    $nama_pasien === '' || strlen($nama_pasien) > 100
    || !in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'], true)
    || $email === false || strlen($email) > 100
    || $alamat === '' || strlen($alamat) > 1000
    || !preg_match('/^[0-9+()\-\s]{8,20}$/', $nomor_telepon)
    || $tanggal_lahir_valid === false || $tanggal_lahir_valid->format('Y-m-d') !== $tanggal_lahir || $tanggal_lahir_valid > $tanggal_hari_ini
) {
    simpan_notifikasi('danger', 'Data registrasi belum valid. Periksa kembali seluruh isian.');
    alihkan('auth/registrasi.php');
}

if (strlen($kata_sandi) < 8 || strlen($konfirmasi_kata_sandi) < 8) {
    simpan_notifikasi('danger', 'Kata sandi harus memiliki minimal 8 karakter.');
    alihkan('auth/registrasi.php');
}

if ($kata_sandi !== $konfirmasi_kata_sandi) {
    simpan_notifikasi('danger', 'Kata sandi harus sama dengan konfirmasi kata sandi.');
    alihkan('auth/registrasi.php');
}

$email = strtolower($email);

try {
    $koneksi_database->beginTransaction();

    $periksa_email = $koneksi_database->prepare('SELECT id_pengguna FROM pengguna WHERE email = :email LIMIT 1');
    $periksa_email->execute(['email' => $email]);

    if ($periksa_email->fetch() !== false) {
        $koneksi_database->rollBack();
        simpan_notifikasi('danger', 'Email tersebut sudah terdaftar. Silakan gunakan email lain atau login.');
        alihkan('auth/registrasi.php');
    }

    $periksa_nik = $koneksi_database->prepare('SELECT id_pasien FROM pasien WHERE nik = :nik LIMIT 1');
    $periksa_nik->execute(['nik' => $nik]);

    if ($periksa_nik->fetch() !== false) {
        $koneksi_database->rollBack();
        simpan_notifikasi('danger', 'NIK sudah terdaftar. Silakan periksa kembali NIK Anda.');
        alihkan('auth/registrasi.php');
    }

    $simpan_pengguna = $koneksi_database->prepare(
        'INSERT INTO pengguna (nama_pengguna, email, kata_sandi, peran)
         VALUES (:nama_pengguna, :email, :kata_sandi, :peran)'
    );
    $simpan_pengguna->execute([
        'nama_pengguna' => $nama_pasien,
        'email' => $email,
        'kata_sandi' => password_hash($kata_sandi, PASSWORD_DEFAULT),
        'peran' => 'pasien',
    ]);

    $id_pengguna = (int) $koneksi_database->lastInsertId();
    $simpan_pasien = $koneksi_database->prepare(
        'INSERT INTO pasien (id_pengguna, nik, nama_pasien, tanggal_lahir, jenis_kelamin, alamat, nomor_telepon, jenis_pembayaran)
         VALUES (:id_pengguna, :nik, :nama_pasien, :tanggal_lahir, :jenis_kelamin, :alamat, :nomor_telepon, :jenis_pembayaran)'
    );
    $simpan_pasien->execute([
        'id_pengguna' => $id_pengguna,
        'nik' => $nik,
        'nama_pasien' => $nama_pasien,
        'tanggal_lahir' => $tanggal_lahir,
        'jenis_kelamin' => $jenis_kelamin,
        'alamat' => $alamat,
        'nomor_telepon' => $nomor_telepon,
        'jenis_pembayaran' => $jenis_pembayaran,
    ]);

    $koneksi_database->commit();
    simpan_notifikasi('success', 'Registrasi berhasil. Silakan login menggunakan akun Anda.');
    alihkan('auth/login.php');
} catch (Throwable $kesalahan) {
    if ($koneksi_database->inTransaction()) {
        $koneksi_database->rollBack();
    }

    $pesan_kesalahan = $kesalahan instanceof PDOException && str_contains(strtolower($kesalahan->getMessage()), 'unik_nik_pasien')
        ? 'NIK sudah terdaftar. Silakan periksa kembali NIK Anda.'
        : 'Registrasi gagal diproses. Silakan coba kembali.';
    simpan_notifikasi('danger', $pesan_kesalahan);
    alihkan('auth/registrasi.php');
}
