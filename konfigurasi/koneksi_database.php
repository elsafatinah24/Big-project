<?php
// ================= KONEKSI DATABASE =================
// Koneksi MySQL menggunakan PDO agar query dapat memakai prepared statement.

declare(strict_types=1);

require_once __DIR__ . '/konstanta.php';

$host_database = 'localhost';
$nama_database = 'antrian_online_puskesmas';
$pengguna_database = 'root';
$kata_sandi_database = '';
$pengkodean_database = 'utf8mb4';

$sumber_data = "mysql:host={$host_database};dbname={$nama_database};charset={$pengkodean_database}";

$pilihan_pdo = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $koneksi_database = new PDO($sumber_data, $pengguna_database, $kata_sandi_database, $pilihan_pdo);
} catch (PDOException $kesalahan) {
    exit('Koneksi database gagal. Periksa konfigurasi database.');
}
