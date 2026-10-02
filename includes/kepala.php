<?php
// ================= TEMPLATE KEPALA HALAMAN =================
// Variabel opsional: $judul_halaman, $memakai_sidebar, dan $stylesheet_tambahan.

declare(strict_types=1);

require_once __DIR__ . '/pembantu.php';

$judul_halaman = $judul_halaman ?? NAMA_APLIKASI;
$memakai_sidebar = $memakai_sidebar ?? false;
$stylesheet_tambahan = $stylesheet_tambahan ?? null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Aplikasi tiket antrean online Puskesmas.">
    <title><?= aman_html($judul_halaman); ?> | <?= aman_html(NAMA_APLIKASI); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= aman_html(url_aplikasi('assets/css/gaya-utama.css?v=' . filemtime(__DIR__ . '/../assets/css/gaya-utama.css'))); ?>">
    <?php if (is_string($stylesheet_tambahan) && $stylesheet_tambahan !== ''): ?>
        <link rel="stylesheet" href="<?= aman_html(url_aplikasi($stylesheet_tambahan)); ?>">
    <?php endif; ?>
</head>
<body class="bg-light">
