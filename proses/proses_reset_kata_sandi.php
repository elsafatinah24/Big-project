<?php
// ================= PROSES RESET KATA SANDI =================
// Memperbarui kata sandi pasien setelah otorisasi reset session tervalidasi.

declare(strict_types=1);

require_once __DIR__ . '/../konfigurasi/koneksi_database.php';
require_once __DIR__ . '/../includes/pembantu.php';

const MASA_BERLAKU_RESET_PASSWORD = 600;

/** Menghapus otorisasi yang tidak lengkap, bukan pasien, atau sudah kedaluwarsa. */
function ambil_otorisasi_reset_password(): ?array
{
    $otorisasi = $_SESSION['otorisasi_reset_password'] ?? null;
    $dibuat_pada = is_array($otorisasi) && isset($otorisasi['dibuat_pada']) ? (int) $otorisasi['dibuat_pada'] : 0;
    if (
        !is_array($otorisasi)
        || !isset($otorisasi['id_pengguna'], $otorisasi['peran'], $otorisasi['dibuat_pada'], $otorisasi['nonce'])
        || (int) $otorisasi['id_pengguna'] < 1
        || $otorisasi['peran'] !== 'pasien'
        || !is_string($otorisasi['nonce'])
        || strlen($otorisasi['nonce']) !== 64
        || !ctype_xdigit($otorisasi['nonce'])
        || $dibuat_pada < 1
        || $dibuat_pada > time()
        || time() - $dibuat_pada > MASA_BERLAKU_RESET_PASSWORD
    ) {
        unset($_SESSION['otorisasi_reset_password']);
        return null;
    }

    return $otorisasi;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alihkan('auth/lupa-kata-sandi.php');
}

$otorisasi = ambil_otorisasi_reset_password();
if ($otorisasi === null) {
    simpan_notifikasi('warning', 'Verifikasi pemulihan tidak valid atau telah kedaluwarsa. Silakan verifikasi kembali.');
    alihkan('auth/lupa-kata-sandi.php');
}

$token_csrf = $_POST['token_csrf'] ?? null;
$kata_sandi_baru = (string) ($_POST['kata_sandi_baru'] ?? '');
$konfirmasi_kata_sandi = (string) ($_POST['konfirmasi_kata_sandi'] ?? '');

if (!token_csrf_valid(is_string($token_csrf) ? $token_csrf : null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid. Silakan coba kembali.');
    alihkan('auth/reset-kata-sandi.php');
}

if (
    $kata_sandi_baru === '' || $konfirmasi_kata_sandi === ''
    || strlen($kata_sandi_baru) < 8
    || $kata_sandi_baru !== $konfirmasi_kata_sandi
) {
    simpan_notifikasi('danger', 'Kata sandi baru minimal 8 karakter dan harus sama dengan konfirmasi.');
    alihkan('auth/reset-kata-sandi.php');
}

try {
    $koneksi_database->beginTransaction();

    // ID hanya berasal dari session otorisasi; role diperiksa kembali di database saat UPDATE.
    $ubah_kata_sandi = $koneksi_database->prepare(
        "UPDATE pengguna
         SET kata_sandi = :kata_sandi
         WHERE id_pengguna = :id_pengguna AND peran = 'pasien'"
    );
    $ubah_kata_sandi->execute([
        'kata_sandi' => password_hash($kata_sandi_baru, PASSWORD_DEFAULT),
        'id_pengguna' => (int) $otorisasi['id_pengguna'],
    ]);

    if ($ubah_kata_sandi->rowCount() !== 1) {
        throw new RuntimeException('Akun pemulihan tidak tersedia.');
    }

    $koneksi_database->commit();
    unset($_SESSION['otorisasi_reset_password']);
    simpan_notifikasi('success', 'Password berhasil diubah. Silakan login dengan password baru.');
    alihkan('auth/login.php');
} catch (Throwable $kesalahan) {
    if ($koneksi_database->inTransaction()) {
        $koneksi_database->rollBack();
    }

    simpan_notifikasi('danger', 'Password gagal diubah. Silakan coba kembali.');
    alihkan('auth/reset-kata-sandi.php');
}
