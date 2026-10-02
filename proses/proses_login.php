<?php
// ================= PROSES LOGIN =================
// Memvalidasi kredensial dan membuat session sesuai peran pengguna.

declare(strict_types=1);

require_once __DIR__ . '/../konfigurasi/koneksi_database.php';
require_once __DIR__ . '/../includes/pembantu.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alihkan('auth/login.php');
}

$token_csrf = $_POST['token_csrf'] ?? null;
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$kata_sandi = $_POST['kata_sandi'] ?? '';

if (!token_csrf_valid(is_string($token_csrf) ? $token_csrf : null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    alihkan('auth/login.php');
}

if ($email === false || $email === null || !is_string($kata_sandi) || $kata_sandi === '') {
    simpan_notifikasi('danger', 'Email dan kata sandi wajib diisi dengan benar.');
    alihkan('auth/login.php');
}

$pernyataan = $koneksi_database->prepare(
    'SELECT id_pengguna, nama_pengguna, email, kata_sandi, peran
     FROM pengguna
     WHERE email = :email
     LIMIT 1'
);
$pernyataan->execute(['email' => $email]);
$pengguna = $pernyataan->fetch();

if ($pengguna === false || !password_verify($kata_sandi, $pengguna['kata_sandi'])) {
    simpan_notifikasi('danger', 'Email atau kata sandi tidak sesuai.');
    alihkan('auth/login.php');
}

session_regenerate_id(true);
$_SESSION['pengguna'] = [
    'id_pengguna' => (int) $pengguna['id_pengguna'],
    'nama_pengguna' => $pengguna['nama_pengguna'],
    'email' => $pengguna['email'],
    'peran' => $pengguna['peran'],
];

simpan_notifikasi('success', 'Login berhasil. Selamat datang, ' . $pengguna['nama_pengguna'] . '.');

if ($pengguna['peran'] === 'admin') {
    alihkan('admin/dashboard.php');
}

if ($pengguna['peran'] === 'dokter') {
    alihkan('dokter/dashboard.php');
}

alihkan('pasien/beranda.php');
