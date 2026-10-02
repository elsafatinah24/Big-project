<?php
// ================= PROSES DATA POLI =================
// Menangani validasi serta proses tambah, ubah, dan hapus data poli oleh Admin.
declare(strict_types=1);
require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';
wajib_peran('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { alihkan('admin/data-poli.php'); }
$token_csrf = $_POST['token_csrf'] ?? null;
$aksi = $_POST['aksi'] ?? '';
if (!token_csrf_valid(is_string($token_csrf) ? $token_csrf : null)) { simpan_notifikasi('danger', 'Permintaan tidak valid.'); alihkan('admin/data-poli.php'); }

// ================= VALIDASI INPUT POLI =================
function ambil_data_poli_valid(): array
{
    $nama_poli = trim((string) ($_POST['nama_poli'] ?? ''));
    $kode_poli = strtoupper(trim((string) ($_POST['kode_poli'] ?? '')));
    $keterangan = trim((string) ($_POST['keterangan'] ?? ''));
    $status_aktif = (string) ($_POST['status_aktif'] ?? '');
    if ($kode_poli === '' || strlen($kode_poli) > 10 || !preg_match('/^[A-Z0-9]+$/', $kode_poli) || $nama_poli === '' || strlen($nama_poli) > 100 || strlen($keterangan) > 1000 || !in_array($status_aktif, ['aktif', 'tidak_aktif'], true)) {
        simpan_notifikasi('danger', 'Data poli belum valid. Periksa kembali isian formulir.');
        alihkan('admin/data-poli.php');
    }
    return [$kode_poli, $nama_poli, $keterangan === '' ? null : $keterangan, $status_aktif];
}

if ($aksi === 'tambah' || $aksi === 'ubah') {
    [$kode_poli, $nama_poli, $keterangan, $status_aktif] = ambil_data_poli_valid();
    $id_poli = isset($_POST['id_poli']) && ctype_digit((string) $_POST['id_poli']) ? (int) $_POST['id_poli'] : 0;
    try {
        if ($aksi === 'tambah') {
            // ================= TAMBAH DATA POLI =================
            $pernyataan = $koneksi_database->prepare('INSERT INTO poli (kode_poli, nama_poli, keterangan, status_aktif) VALUES (:kode_poli, :nama_poli, :keterangan, :status_aktif)');
            $pernyataan->execute(['kode_poli' => $kode_poli, 'nama_poli' => $nama_poli, 'keterangan' => $keterangan, 'status_aktif' => $status_aktif]);
            simpan_notifikasi('success', 'Data poli berhasil ditambahkan.');
        } elseif ($id_poli > 0) {
            // ================= UBAH DATA POLI =================
            $pernyataan = $koneksi_database->prepare('UPDATE poli SET kode_poli = :kode_poli, nama_poli = :nama_poli, keterangan = :keterangan, status_aktif = :status_aktif WHERE id_poli = :id_poli');
            $pernyataan->execute(['kode_poli' => $kode_poli, 'nama_poli' => $nama_poli, 'keterangan' => $keterangan, 'status_aktif' => $status_aktif, 'id_poli' => $id_poli]);
            simpan_notifikasi($pernyataan->rowCount() > 0 ? 'success' : 'warning', $pernyataan->rowCount() > 0 ? 'Data poli berhasil diperbarui.' : 'Tidak ada perubahan data atau data poli tidak ditemukan.');
        } else { simpan_notifikasi('danger', 'Identitas poli tidak valid.'); }
    } catch (PDOException $kesalahan) {
        simpan_notifikasi('danger', $kesalahan->getCode() === '23000' ? 'Nama poli sudah tersedia.' : 'Data poli gagal disimpan.');
    }
    alihkan('admin/data-poli.php');
}

if ($aksi === 'hapus') {
    // ================= HAPUS DATA POLI =================
    $id_poli = isset($_POST['id_poli']) && ctype_digit((string) $_POST['id_poli']) ? (int) $_POST['id_poli'] : 0;
    if ($id_poli < 1) { simpan_notifikasi('danger', 'Identitas poli tidak valid.'); alihkan('admin/data-poli.php'); }
    try {
        $hapus_poli = $koneksi_database->prepare('DELETE FROM poli WHERE id_poli = :id_poli');
        $hapus_poli->execute(['id_poli' => $id_poli]);
        simpan_notifikasi($hapus_poli->rowCount() === 1 ? 'success' : 'danger', $hapus_poli->rowCount() === 1 ? 'Data poli berhasil dihapus.' : 'Data poli tidak ditemukan.');
    } catch (PDOException $kesalahan) {
        simpan_notifikasi('danger', 'Data poli tidak dapat dihapus karena sudah digunakan pada data antrean.');
    }
    alihkan('admin/data-poli.php');
}

simpan_notifikasi('danger', 'Aksi data poli tidak dikenali.');
alihkan('admin/data-poli.php');
