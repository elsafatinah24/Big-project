<?php
// ================= PROSES CRUD ANTREAN =================
// Menangani tambah, edit, dan hapus antrean secara konsisten.
declare(strict_types=1);
require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';
wajib_peran('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !token_csrf_valid($_POST['token_csrf'] ?? null)) { simpan_notifikasi('danger', 'Permintaan tidak valid.'); alihkan('admin/data-antrean.php'); }
$aksi=(string)($_POST['aksi']??'');$id=(int)($_POST['id_antrean']??0);
try {
    $koneksi_database->beginTransaction();
    if ($aksi === 'hapus') {
        if ($id < 1) throw new RuntimeException('Identitas antrean tidak valid.');
        $q=$koneksi_database->prepare('DELETE FROM antrean WHERE id_antrean=:id');$q->execute(['id'=>$id]);
        if (!$q->rowCount()) throw new RuntimeException('Data antrean tidak ditemukan.');
        $pesan='Data antrean berhasil dihapus.';
    } else {
        $id_pasien=(int)($_POST['id_pasien']??0);$id_poli=(int)($_POST['id_poli']??0);$tanggal=(string)($_POST['tanggal_antrean']??'');$status=(string)($_POST['status_antrean']??'');
        $tanggal_valid=DateTime::createFromFormat('Y-m-d',$tanggal);
        if($id_pasien<1||$id_poli<1||!$tanggal_valid||$tanggal_valid->format('Y-m-d')!==$tanggal||!in_array($status,['menunggu','dipanggil','selesai','dibatalkan'],true)) throw new RuntimeException('Data antrean belum valid.');
        $cek=$koneksi_database->prepare('SELECT id_pasien FROM pasien WHERE id_pasien=:id');$cek->execute(['id'=>$id_pasien]);if(!$cek->fetchColumn())throw new RuntimeException('Pasien tidak ditemukan.');
        $cek=$koneksi_database->prepare('SELECT id_poli FROM poli WHERE id_poli=:id');$cek->execute(['id'=>$id_poli]);if(!$cek->fetchColumn())throw new RuntimeException('Poli tidak ditemukan.');
        if($aksi==='tambah'){$q=$koneksi_database->prepare('SELECT COALESCE(MAX(nomor_antrean),0)+1 FROM antrean WHERE id_poli=:p AND tanggal_antrean=:t FOR UPDATE');$q->execute(['p'=>$id_poli,'t'=>$tanggal]);$nomor=(int)$q->fetchColumn();$q=$koneksi_database->prepare('INSERT INTO antrean(id_pasien,id_poli,nomor_antrean,tanggal_antrean,status_antrean,waktu_dipanggil,waktu_selesai) VALUES(:pa,:po,:n,:t,:s,:panggil,:selesai)');$q->execute(['pa'=>$id_pasien,'po'=>$id_poli,'n'=>$nomor,'t'=>$tanggal,'s'=>$status,'panggil'=>$status==='dipanggil'?date('Y-m-d H:i:s'):null,'selesai'=>$status==='selesai'?date('Y-m-d H:i:s'):null]);$pesan='Antrean berhasil ditambahkan.';}
        elseif($aksi==='ubah'&&$id>0){$q=$koneksi_database->prepare('SELECT id_poli,tanggal_antrean,nomor_antrean,waktu_dipanggil,waktu_selesai FROM antrean WHERE id_antrean=:id FOR UPDATE');$q->execute(['id'=>$id]);$lama=$q->fetch();if(!$lama)throw new RuntimeException('Data antrean tidak ditemukan.');$nomor=(int)$lama['nomor_antrean'];if((int)$lama['id_poli']!==$id_poli||$lama['tanggal_antrean']!==$tanggal){$q=$koneksi_database->prepare('SELECT COALESCE(MAX(nomor_antrean),0)+1 FROM antrean WHERE id_poli=:p AND tanggal_antrean=:t FOR UPDATE');$q->execute(['p'=>$id_poli,'t'=>$tanggal]);$nomor=(int)$q->fetchColumn();}$panggil=$status==='dipanggil'&&$lama['waktu_dipanggil']===null?date('Y-m-d H:i:s'):$lama['waktu_dipanggil'];$selesai=$status==='selesai'&&$lama['waktu_selesai']===null?date('Y-m-d H:i:s'):$lama['waktu_selesai'];$q=$koneksi_database->prepare('UPDATE antrean SET id_pasien=:pa,id_poli=:po,nomor_antrean=:n,tanggal_antrean=:t,status_antrean=:s,waktu_dipanggil=:p,waktu_selesai=:e WHERE id_antrean=:id');$q->execute(['pa'=>$id_pasien,'po'=>$id_poli,'n'=>$nomor,'t'=>$tanggal,'s'=>$status,'p'=>$panggil,'e'=>$selesai,'id'=>$id]);$pesan='Antrean berhasil diperbarui.';}else throw new RuntimeException('Aksi tidak dikenali.');
    }
    $koneksi_database->commit();simpan_notifikasi('success',$pesan);
} catch (Throwable $e) { if($koneksi_database->inTransaction())$koneksi_database->rollBack();simpan_notifikasi('danger',$e instanceof PDOException?'Proses antrean gagal.':$e->getMessage()); }
alihkan('admin/data-antrean.php');
