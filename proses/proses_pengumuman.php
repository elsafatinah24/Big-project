<?php
// ================= PROSES PENGUMUMAN =================
// Menangani CRUD pengumuman beserta upload dan penghapusan gambar oleh Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !token_csrf_valid($_POST['token_csrf'] ?? null)) {
    simpan_notifikasi('danger', 'Permintaan tidak valid.');
    alihkan('admin/data-pengumuman.php');
}

$aksi = (string) ($_POST['aksi'] ?? '');
$id_pengumuman = isset($_POST['id_pengumuman']) && ctype_digit((string) $_POST['id_pengumuman'])
    ? (int) $_POST['id_pengumuman']
    : 0;

/** Memvalidasi data pengumuman yang dikirim dari form. */
function data_pengumuman_valid(): array
{
    $judul = trim((string) ($_POST['judul'] ?? ''));
    $isi_pengumuman = trim((string) ($_POST['isi_pengumuman'] ?? ''));
    $tanggal_publikasi = trim((string) ($_POST['tanggal_publikasi'] ?? ''));
    $status_aktif = (string) ($_POST['status_aktif'] ?? '');
    $tanggal_valid = DateTime::createFromFormat('Y-m-d\TH:i', $tanggal_publikasi);

    if (
        $judul === '' || strlen($judul) > 200
        || $isi_pengumuman === '' || strlen($isi_pengumuman) > 10000
        || $tanggal_valid === false || $tanggal_valid->format('Y-m-d\TH:i') !== $tanggal_publikasi
        || !in_array($status_aktif, ['aktif', 'tidak_aktif'], true)
    ) {
        throw new RuntimeException('Data pengumuman belum valid.');
    }

    return [$judul, $isi_pengumuman, $tanggal_valid->format('Y-m-d H:i:s'), $status_aktif];
}

/** Menghapus gambar yang tersimpan tanpa mengizinkan traversal path. */
function hapus_gambar_pengumuman(?string $nama_file): void
{
    if ($nama_file === null || $nama_file === '' || basename($nama_file) !== $nama_file) {
        return;
    }

    $root_proyek = realpath(__DIR__ . '/..');

    if ($root_proyek === false) {
        return;
    }

    $lokasi_file = $root_proyek . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pengumuman' . DIRECTORY_SEPARATOR . $nama_file;

    if (is_file($lokasi_file)) {
        @unlink($lokasi_file);
    }
}

/** Mengubah kode error unggahan PHP menjadi pesan yang dapat ditindaklanjuti. */
function pesan_error_upload(int $kode_error): string
{
    return match ($kode_error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran gambar melebihi batas upload PHP atau batas 2 MB.',
        UPLOAD_ERR_PARTIAL => 'Gambar hanya terunggah sebagian. Silakan coba kembali.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara PHP untuk upload tidak tersedia.',
        UPLOAD_ERR_CANT_WRITE => 'PHP tidak dapat menulis file upload ke penyimpanan.',
        UPLOAD_ERR_EXTENSION => 'Upload gambar dihentikan oleh ekstensi PHP.',
        default => 'Upload gambar gagal diproses.',
    };
}

/** Memvalidasi dan menyimpan gambar unggahan dengan nama unik. */
function simpan_gambar_pengumuman(array $berkas): ?string
{
    $kode_error = $berkas['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($kode_error === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($kode_error !== UPLOAD_ERR_OK || !isset($berkas['tmp_name'], $berkas['name'], $berkas['size'])) {
        error_log('Upload pengumuman gagal dengan kode PHP: ' . $kode_error);
        throw new RuntimeException(pesan_error_upload((int) $kode_error));
    }

    if ((int) $berkas['size'] < 1 || (int) $berkas['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran gambar maksimal 2 MB.');
    }

    if (!is_uploaded_file($berkas['tmp_name'])) {
        error_log('File sementara upload pengumuman tidak valid: ' . (string) $berkas['tmp_name']);
        throw new RuntimeException('File sementara upload tidak valid. Periksa konfigurasi upload PHP.');
    }

    $ekstensi = strtolower(pathinfo((string) $berkas['name'], PATHINFO_EXTENSION));
    $tipe_mime = (new finfo(FILEINFO_MIME_TYPE))->file($berkas['tmp_name']);
    $mime_diizinkan = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    if (!isset($mime_diizinkan[$tipe_mime]) || !in_array($ekstensi, $mime_diizinkan[$tipe_mime], true)) {
        throw new RuntimeException('Gambar harus berformat JPG, JPEG, PNG, atau WEBP.');
    }

    $root_proyek = realpath(__DIR__ . '/..');

    if ($root_proyek === false) {
        throw new RuntimeException('Root project untuk upload gambar tidak ditemukan.');
    }

    $direktori_upload = $root_proyek . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pengumuman';

    if (!is_dir($direktori_upload) && !mkdir($direktori_upload, 0777, true) && !is_dir($direktori_upload)) {
        throw new RuntimeException('Folder upload gambar tidak dapat dibuat.');
    }

    @chmod($direktori_upload, 0777);
    clearstatcache(true, $direktori_upload);

    if (!is_writable($direktori_upload)) {
        error_log('Folder upload pengumuman tidak dapat ditulis: ' . $direktori_upload);
        throw new RuntimeException('Folder uploads/pengumuman tidak memiliki izin tulis untuk PHP.');
    }

    $nama_file = bin2hex(random_bytes(16)) . '.' . ($ekstensi === 'jpeg' ? 'jpg' : $ekstensi);

    $lokasi_tujuan = $direktori_upload . DIRECTORY_SEPARATOR . $nama_file;

    if (!move_uploaded_file($berkas['tmp_name'], $lokasi_tujuan)) {
        $kesalahan_terakhir = error_get_last();
        $detail_error = $kesalahan_terakhir['message'] ?? 'move_uploaded_file() mengembalikan false.';
        error_log('Gambar pengumuman gagal disimpan. Tujuan: ' . $lokasi_tujuan . '. Detail: ' . $detail_error);
        throw new RuntimeException('Gambar gagal disimpan. Detail telah dicatat pada error log PHP.');
    }

    return $nama_file;
}

$gambar_baru = null;

try {
    if ($aksi === 'tambah') {
        [$judul, $isi_pengumuman, $tanggal_publikasi, $status_aktif] = data_pengumuman_valid();
        $gambar_baru = simpan_gambar_pengumuman($_FILES['gambar'] ?? []);

        $koneksi_database->beginTransaction();
        $simpan_pengumuman = $koneksi_database->prepare(
            'INSERT INTO pengumuman (judul, isi_pengumuman, gambar, tanggal_publikasi, status_aktif)
             VALUES (:judul, :isi_pengumuman, :gambar, :tanggal_publikasi, :status_aktif)'
        );
        $simpan_pengumuman->execute([
            'judul' => $judul,
            'isi_pengumuman' => $isi_pengumuman,
            'gambar' => $gambar_baru,
            'tanggal_publikasi' => $tanggal_publikasi,
            'status_aktif' => $status_aktif,
        ]);
        $koneksi_database->commit();
        $gambar_baru = null;
        simpan_notifikasi('success', 'Pengumuman berhasil ditambahkan.');
    } elseif ($aksi === 'ubah') {
        if ($id_pengumuman < 1) {
            throw new RuntimeException('Identitas pengumuman tidak valid.');
        }

        [$judul, $isi_pengumuman, $tanggal_publikasi, $status_aktif] = data_pengumuman_valid();
        $koneksi_database->beginTransaction();

        $ambil_gambar_lama = $koneksi_database->prepare(
            'SELECT gambar FROM pengumuman WHERE id_pengumuman = :id_pengumuman LIMIT 1 FOR UPDATE'
        );
        $ambil_gambar_lama->execute(['id_pengumuman' => $id_pengumuman]);
        $pengumuman_lama = $ambil_gambar_lama->fetch();

        if ($pengumuman_lama === false) {
            throw new RuntimeException('Pengumuman tidak ditemukan.');
        }

        $gambar_baru = simpan_gambar_pengumuman($_FILES['gambar'] ?? []);
        $gambar_tujuan = $gambar_baru ?? $pengumuman_lama['gambar'];
        $ubah_pengumuman = $koneksi_database->prepare(
            'UPDATE pengumuman
             SET judul = :judul, isi_pengumuman = :isi_pengumuman, gambar = :gambar,
                 tanggal_publikasi = :tanggal_publikasi, status_aktif = :status_aktif
             WHERE id_pengumuman = :id_pengumuman'
        );
        $ubah_pengumuman->execute([
            'judul' => $judul,
            'isi_pengumuman' => $isi_pengumuman,
            'gambar' => $gambar_tujuan,
            'tanggal_publikasi' => $tanggal_publikasi,
            'status_aktif' => $status_aktif,
            'id_pengumuman' => $id_pengumuman,
        ]);
        $koneksi_database->commit();

        if ($gambar_baru !== null) {
            hapus_gambar_pengumuman($pengumuman_lama['gambar']);
        }

        $gambar_baru = null;
        simpan_notifikasi('success', 'Pengumuman berhasil diperbarui.');
    } elseif ($aksi === 'hapus') {
        if ($id_pengumuman < 1) {
            throw new RuntimeException('Identitas pengumuman tidak valid.');
        }

        $koneksi_database->beginTransaction();
        $ambil_gambar = $koneksi_database->prepare(
            'SELECT gambar FROM pengumuman WHERE id_pengumuman = :id_pengumuman LIMIT 1 FOR UPDATE'
        );
        $ambil_gambar->execute(['id_pengumuman' => $id_pengumuman]);
        $pengumuman = $ambil_gambar->fetch();

        if ($pengumuman === false) {
            throw new RuntimeException('Pengumuman tidak ditemukan.');
        }

        $hapus_pengumuman = $koneksi_database->prepare('DELETE FROM pengumuman WHERE id_pengumuman = :id_pengumuman');
        $hapus_pengumuman->execute(['id_pengumuman' => $id_pengumuman]);
        $koneksi_database->commit();
        hapus_gambar_pengumuman($pengumuman['gambar']);
        simpan_notifikasi('success', 'Pengumuman berhasil dihapus.');
    } else {
        throw new RuntimeException('Aksi pengumuman tidak dikenali.');
    }
} catch (Throwable $kesalahan) {
    if ($koneksi_database->inTransaction()) {
        $koneksi_database->rollBack();
    }

    if ($gambar_baru !== null) {
        hapus_gambar_pengumuman($gambar_baru);
    }

    simpan_notifikasi(
        'danger',
        $kesalahan instanceof RuntimeException
            ? $kesalahan->getMessage()
            : 'Pengumuman gagal diproses. Silakan coba kembali.'
    );
}

alihkan('admin/data-pengumuman.php');
