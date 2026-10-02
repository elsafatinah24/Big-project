<?php
// ================= KONFIGURASI SESSION =================
// Session dipakai untuk menyimpan status login dan pesan sementara.

declare(strict_types=1);

require_once __DIR__ . '/konstanta.php';

if (session_status() === PHP_SESSION_NONE) {
    $menggunakan_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'httponly' => true,
        'secure' => $menggunakan_https,
        'samesite' => 'Lax',
    ]);

    session_start();
}
