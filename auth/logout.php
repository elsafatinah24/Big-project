<?php
// ================= LOGOUT =================
// Mengakhiri session login pengguna dengan aman.

declare(strict_types=1);

require_once __DIR__ . '/../includes/pembantu.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parameter_cookie = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $parameter_cookie['path'], $parameter_cookie['domain'], (bool) $parameter_cookie['secure'], (bool) $parameter_cookie['httponly']);
}

session_destroy();
session_start();
simpan_notifikasi('success', 'Anda telah logout.');
alihkan('auth/login.php');
