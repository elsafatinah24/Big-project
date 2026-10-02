<?php
// ================= PROSES LUPA KATA SANDI =================
// Memverifikasi identitas pasien dan membuat otorisasi reset sementara dalam session.

declare(strict_types=1);

require_once __DIR__ . '/../konfigurasi/koneksi_database.php';
require_once __DIR__ . '/../includes/pembantu.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alihkan('auth/lupa-kata-sandi.php');
}

$token_csrf = $_POST['token_csrf'] ?? null;
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$nik = trim((string) ($_POST['nik'] ?? ''));
$tanggal_lahir = trim((string) ($_POST['tanggal_lahir'] ?? ''));

const BATAS_PERCOBAAN_VERIFIKASI_RESET = 5;
const JENDELA_PERCOBAAN_VERIFIKASI_RESET = 900;

/** Mencatat percobaan gagal tanpa menyimpan data verifikasi yang dikirim pengguna. */
function catat_percobaan_verifikasi_reset(): void
{
    $data_batas = $_SESSION['batas_verifikasi_reset_password'] ?? [];
    $sekarang = time();
    $mulai_pada = isset($data_batas['mulai_pada']) ? (int) $data_batas['mulai_pada'] : $sekarang;
    $jumlah = isset($data_batas['jumlah']) ? (int) $data_batas['jumlah'] : 0;

    if ($sekarang - $mulai_pada >= JENDELA_PERCOBAAN_VERIFIKASI_RESET) {
        $mulai_pada = $sekarang;
        $jumlah = 0;
    }

    $_SESSION['batas_verifikasi_reset_password'] = [
        'mulai_pada' => $mulai_pada,
        'jumlah' => $jumlah + 1,
    ];
}

/** Memeriksa batas percobaan verifikasi berbasis session selama lima belas menit. */
function batas_verifikasi_reset_terlampaui(): bool
{
    $data_batas = $_SESSION['batas_verifikasi_reset_password'] ?? null;
    if (!is_array($data_batas)) {
        return false;
    }

    $mulai_pada = isset($data_batas['mulai_pada']) ? (int) $data_batas['mulai_pada'] : 0;
    if ($mulai_pada < 1 || time() - $mulai_pada >= JENDELA_PERCOBAAN_VERIFIKASI_RESET) {
        unset($_SESSION['batas_verifikasi_reset_password']);
        return false;
    }

    return isset($data_batas['jumlah']) && (int) $data_batas['jumlah'] >= BATAS_PERCOBAAN_VERIFIKASI_RESET;
}

if (!token_csrf_valid(is_string($token_csrf) ? $token_csrf : null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    alihkan('auth/lupa-kata-sandi.php');
}

if (batas_verifikasi_reset_terlampaui()) {
    simpan_notifikasi('danger', 'Terlalu banyak percobaan. Silakan tunggu beberapa saat sebelum mencoba kembali.');
    alihkan('auth/lupa-kata-sandi.php');
}

// NIK dan tanggal lahir wajib valid sebelum query; pesan kegagalan tetap umum.
$tanggal_lahir_valid = DateTime::createFromFormat('Y-m-d', $tanggal_lahir);
if (
    $email === false || $email === null || strlen($email) > 100
    || !preg_match('/^[0-9]{16}$/', $nik)
    || $tanggal_lahir_valid === false
    || $tanggal_lahir_valid->format('Y-m-d') !== $tanggal_lahir
    || $tanggal_lahir_valid > new DateTime('today')
) {
    catat_percobaan_verifikasi_reset();
    simpan_notifikasi('danger', 'Data verifikasi tidak cocok atau pemulihan mandiri belum tersedia.');
    alihkan('auth/lupa-kata-sandi.php');
}

$verifikasi_pasien = $koneksi_database->prepare(
    "SELECT pengguna.id_pengguna
     FROM pengguna
     INNER JOIN pasien ON pasien.id_pengguna = pengguna.id_pengguna
     WHERE pengguna.email = :email
       AND pengguna.peran = 'pasien'
       AND pasien.nik = :nik
       AND pasien.tanggal_lahir = :tanggal_lahir
     LIMIT 1"
);
$verifikasi_pasien->execute([
    'email' => strtolower($email),
    'nik' => $nik,
    'tanggal_lahir' => $tanggal_lahir,
]);
$id_pengguna = $verifikasi_pasien->fetchColumn();

if ($id_pengguna === false) {
    catat_percobaan_verifikasi_reset();
    simpan_notifikasi('danger', 'Data verifikasi tidak cocok atau pemulihan mandiri belum tersedia.');
    alihkan('auth/lupa-kata-sandi.php');
}

session_regenerate_id(true);
$_SESSION['otorisasi_reset_password'] = [
    'id_pengguna' => (int) $id_pengguna,
    'peran' => 'pasien',
    'dibuat_pada' => time(),
    'nonce' => bin2hex(random_bytes(32)),
];
unset($_SESSION['batas_verifikasi_reset_password']);

simpan_notifikasi('success', 'Verifikasi berhasil. Silakan buat kata sandi baru.');
alihkan('auth/reset-kata-sandi.php');
