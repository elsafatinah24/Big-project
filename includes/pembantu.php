<?php
// ================= FUNGSI PEMBANTU =================
// Kumpulan fungsi umum untuk URL, keamanan keluaran, redirect, dan notifikasi.

declare(strict_types=1);

require_once __DIR__ . '/../konfigurasi/konstanta.php';
require_once __DIR__ . '/../konfigurasi/sesi.php';

/** Membentuk URL internal aplikasi. */
function url_aplikasi(string $lokasi = ''): string
{
    $url_dasar = rtrim(URL_DASAR, '/');

    // Anchor landing page tidak memerlukan nama berkas dan selalu merujuk ke halaman utama aplikasi.
    if (str_starts_with($lokasi, '#')) {
        return $url_dasar . '/' . $lokasi;
    }

    return $url_dasar . '/' . ltrim($lokasi, '/');
}

/** Mengamankan teks sebelum ditampilkan ke HTML. */
function aman_html(?string $teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

/** Mengalihkan pengguna ke halaman internal aplikasi. */
function alihkan(string $lokasi): never
{
    header('Location: ' . url_aplikasi($lokasi));
    exit;
}

/** Menyimpan notifikasi untuk ditampilkan sekali pada halaman berikutnya. */
function simpan_notifikasi(string $jenis, string $pesan): void
{
    $_SESSION['notifikasi'] = [
        'jenis' => $jenis,
        'pesan' => $pesan,
    ];
}

/** Mengambil sekaligus menghapus notifikasi session. */
function ambil_notifikasi(): ?array
{
    $notifikasi = $_SESSION['notifikasi'] ?? null;
    unset($_SESSION['notifikasi']);

    return $notifikasi;
}

/** Memeriksa apakah pengguna telah login. */
function sudah_login(): bool
{
    return isset($_SESSION['pengguna']['id_pengguna'], $_SESSION['pengguna']['peran']);
}

/** Membuat atau mengambil token CSRF untuk form yang mengubah data. */
function token_csrf(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['token_csrf'];
}

/** Memvalidasi token CSRF yang dikirimkan dari form. */
function token_csrf_valid(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['token_csrf'])
        && hash_equals($_SESSION['token_csrf'], $token);
}
