<?php
// ================= TEMPLATE NOTIFIKASI =================
// Menampilkan pesan session satu kali setelah proses atau pengalihan halaman.

declare(strict_types=1);

$notifikasi = ambil_notifikasi();
if ($notifikasi !== null):
    $jenis_notifikasi = in_array($notifikasi['jenis'], ['success', 'danger', 'warning', 'info'], true)
        ? $notifikasi['jenis']
        : 'info';
?>
    <div class="alert alert-<?= aman_html($jenis_notifikasi); ?> alert-dismissible fade show" role="alert">
        <?= aman_html($notifikasi['pesan']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>
