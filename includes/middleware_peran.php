<?php
// ================= MIDDLEWARE AKSES =================
// Memastikan halaman hanya dapat dibuka oleh pengguna yang sudah login dan berperan sesuai.

declare(strict_types=1);

require_once __DIR__ . '/pembantu.php';

/** Membatasi halaman untuk pengguna yang sudah login. */
function wajib_login(): void
{
    if (!sudah_login()) {
        simpan_notifikasi('warning', 'Silakan login untuk mengakses halaman tersebut.');
        alihkan('auth/login.php');
    }
}

/** Membatasi halaman untuk satu peran tertentu. */
function wajib_peran(string $peran): void
{
    wajib_login();

    if ($_SESSION['pengguna']['peran'] !== $peran) {
        simpan_notifikasi('danger', 'Anda tidak memiliki hak akses ke halaman tersebut.');

        $halaman_tujuan = match ($_SESSION['pengguna']['peran']) {
            'admin' => 'admin/dashboard.php',
            'dokter' => 'dokter/dashboard.php',
            default => 'pasien/beranda.php',
        };
        alihkan($halaman_tujuan);
    }
}
