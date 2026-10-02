<?php
// ================= TEMPLATE KAKI HALAMAN =================
// Penutup HTML dan pemanggilan JavaScript Bootstrap.

declare(strict_types=1);
?>
<footer class="border-top bg-white py-3 mt-auto">
    <div class="container text-center text-secondary small">
        &copy; <?= date('Y'); ?> <?= aman_html(NAMA_APLIKASI); ?>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= aman_html(url_aplikasi('assets/js/aplikasi.js?v=' . filemtime(__DIR__ . '/../assets/js/aplikasi.js'))); ?>"></script>
</body>
</html>
