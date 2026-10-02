<?php
// ================= LANDING PAGE =================
// Halaman publik utama yang menampilkan informasi Puskesmas sesuai PRD.

declare(strict_types=1);

require_once __DIR__ . '/konfigurasi/koneksi_database.php';

// ================= JADWAL PELAYANAN AKTIF =================
// Menyiapkan seluruh jadwal aktif untuk ditampilkan pada landing page.
$ambil_jadwal_aktif = $koneksi_database->prepare(
    "SELECT hari, jam_buka, jam_tutup, keterangan
     FROM jadwal_pelayanan
     WHERE status_aktif = 'aktif'
     ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jam_buka ASC"
);
$ambil_jadwal_aktif->execute();
$daftar_jadwal_aktif = $ambil_jadwal_aktif->fetchAll();

// ================= PENGUMUMAN AKTIF =================
// Menyiapkan pengumuman aktif terbaru untuk ditampilkan pada landing page.
$ambil_pengumuman_aktif = $koneksi_database->prepare(
    "SELECT judul, isi_pengumuman, gambar, tanggal_publikasi
     FROM pengumuman
     WHERE status_aktif = 'aktif'
     ORDER BY tanggal_publikasi DESC, id_pengumuman DESC"
);
$ambil_pengumuman_aktif->execute();
$daftar_pengumuman_aktif = $ambil_pengumuman_aktif->fetchAll();

$judul_halaman = 'Beranda';
require_once __DIR__ . '/includes/kepala.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main>
    <!-- ================= HERO ================= -->
    <section class="bagian-hero py-5 py-lg-6">
        <div class="container py-lg-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="lencana-hero">Pelayanan kesehatan untuk masyarakat</span>
                    <h1 class="judul-hero mt-3">Antrean Puskesmas, lebih mudah dari mana saja.</h1>
                    <p class="lead text-secondary mt-3 mb-4">Ambil nomor antrean secara online, pilih poli sesuai kebutuhan, dan pantau status antrean Anda dengan praktis.</p>
                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <a class="btn btn-success btn-lg px-4" href="<?= aman_html(url_aplikasi('auth/registrasi.php')); ?>">Ambil Nomor Antrean</a>
                        <a class="btn btn-outline-success btn-lg px-4" href="#layanan">Lihat Layanan</a>
                    </div>
                    <div class="d-flex flex-wrap gap-4 mt-4 teks-kepercayaan">
                        <span>✓ Mudah digunakan</span>
                        <span>✓ Informasi antrean jelas</span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="ilustrasi-kesehatan mx-auto" aria-label="Ilustrasi pelayanan kesehatan">
                        <div class="lingkaran-dekorasi lingkaran-satu"></div>
                        <div class="lingkaran-dekorasi lingkaran-dua"></div>
                        <div class="kartu-ilustrasi kartu-dokter">
                            <div class="ikon-dokter">+</div>
                            <div>
                                <strong>Pelayanan Puskesmas</strong>
                                <span>Siap membantu Anda</span>
                            </div>
                        </div>
                        <div class="kartu-ilustrasi kartu-antrean">
                            <span class="nomor-ilustrasi">A-12</span>
                            <span>Nomor antrean Anda</span>
                        </div>
                        <div class="kartu-ilustrasi kartu-status">
                            <span class="titik-status"></span>
                            <span>Antrean aktif</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= PROFIL ================= -->
    <section class="py-5" id="profil">
        <div class="container py-lg-4">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-5">
                    <div class="panel-profil">
                        <img class="panel-profil-gambar" src="<?= aman_html(url_aplikasi('assets/gambar/profile.jpeg')); ?>" alt="Gedung Puskesmas">
                        <div class="panel-profil-ikon">+</div>
                        <p class="mb-0">Kesehatan masyarakat adalah prioritas pelayanan kami.</p>
                    </div>
                </div>
                <div class="col-lg-7">
                    <p class="teks-label">Profil Puskesmas</p>
                    <h2 class="judul-bagian">Pelayanan yang dekat, ramah, dan terorganisir.</h2>
                    <p class="text-secondary">Puskesmas menyediakan pelayanan kesehatan bagi masyarakat dengan mengutamakan kemudahan akses informasi dan kenyamanan selama proses pelayanan.</p>
                    <p class="text-secondary mb-0">Melalui aplikasi ini, pasien dapat mempersiapkan kunjungan dengan mengambil nomor antrean secara online sebelum datang ke Puskesmas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= LAYANAN ================= -->
    <section class="bagian-latar-hijau py-5" id="layanan">
        <div class="container py-lg-4">
            <div class="text-center mx-auto batas-judul">
                <p class="teks-label">Informasi Layanan</p>
                <h2 class="judul-bagian">Pilih poli sesuai kebutuhan Anda</h2>
                <p class="text-secondary">Pasien dapat memilih poli saat mengambil nomor antrean online.</p>
            </div>
            <div class="row g-4 mt-1">
                <div class="col-md-6 col-lg-4">
                    <article class="kartu-layanan h-100">
                        <div class="ikon-layanan">01</div>
                        <h3 class="h5 mt-4">Pilih Poli</h3>
                        <p class="text-secondary mb-0">Tentukan poli tujuan sesuai kebutuhan pelayanan kesehatan Anda.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="kartu-layanan h-100">
                        <div class="ikon-layanan">02</div>
                        <h3 class="h5 mt-4">Ambil Antrean</h3>
                        <p class="text-secondary mb-0">Dapatkan nomor antrean secara online tanpa harus datang lebih awal.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="kartu-layanan h-100">
                        <div class="ikon-layanan">03</div>
                        <h3 class="h5 mt-4">Pantau Status</h3>
                        <p class="text-secondary mb-0">Lihat perkembangan status antrean Anda dengan informasi yang jelas.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= JADWAL ================= -->
    <section class="py-5" id="jadwal">
        <div class="container py-lg-4">
            <div class="row align-items-end mb-4">
                <div class="col-lg-7">
                    <p class="teks-label">Jadwal Pelayanan</p>
                    <h2 class="judul-bagian mb-0">Waktu pelayanan Puskesmas</h2>
                </div>
                <div class="col-lg-5"><p class="text-secondary mb-0">Pastikan datang sesuai jadwal pelayanan yang berlaku.</p></div>
            </div>
            <?php if ($daftar_jadwal_aktif === []): ?>
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-lg-5 text-center">
                        <div class="ikon-informasi mx-auto mb-3">i</div>
                        <h3 class="h5">Belum ada jadwal pelayanan aktif</h3>
                        <p class="text-secondary mb-0">Silakan kunjungi halaman ini kembali untuk melihat jadwal pelayanan terbaru.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-success"><tr><th class="ps-4">Hari</th><th>Jam Pelayanan</th><th class="pe-4">Keterangan</th></tr></thead>
                            <tbody>
                                <?php foreach ($daftar_jadwal_aktif as $jadwal): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold"><?= aman_html($jadwal['hari']); ?></td>
                                        <td><?= aman_html(substr($jadwal['jam_buka'], 0, 5) . ' - ' . substr($jadwal['jam_tutup'], 0, 5)); ?></td>
                                        <td class="pe-4 text-secondary"><?= $jadwal['keterangan'] !== null && $jadwal['keterangan'] !== '' ? aman_html($jadwal['keterangan']) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================= BERITA ================= -->
    <section class="bagian-latar-hijau py-5" id="berita">
        <div class="container py-lg-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
                <div><p class="teks-label">Berita & Pengumuman</p><h2 class="judul-bagian mb-0">Informasi terbaru</h2></div>
                <span class="text-secondary">Informasi terbaru dari Puskesmas.</span>
            </div>
            <?php if ($daftar_pengumuman_aktif === []): ?>
                <div class="card border-0 rounded-4 shadow-sm">
                    <div class="card-body p-4 p-lg-5 text-center">
                        <div class="ikon-informasi mx-auto mb-3">i</div>
                        <h3 class="h5">Belum ada pengumuman aktif</h3>
                        <p class="text-secondary mb-0">Silakan kunjungi halaman ini kembali untuk mendapatkan informasi pelayanan Puskesmas.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($daftar_pengumuman_aktif as $pengumuman): ?>
                        <div class="col-md-6 col-xl-4">
                            <article class="card border-0 rounded-4 shadow-sm h-100">
                                <?php $gambar_pengumuman = is_string($pengumuman['gambar']) ? basename($pengumuman['gambar']) : ''; ?>
                                <?php if ($gambar_pengumuman !== '' && is_file(__DIR__ . '/uploads/pengumuman/' . $gambar_pengumuman)): ?>
                                    <img class="card-img-top" src="<?= aman_html(url_aplikasi('uploads/pengumuman/' . rawurlencode($gambar_pengumuman))); ?>" alt="<?= aman_html($pengumuman['judul']); ?>" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light border-bottom d-flex align-items-center justify-content-center text-secondary" style="height: 200px;">Tidak ada gambar</div>
                                <?php endif; ?>
                                <div class="card-body p-4">
                                    <small class="text-success fw-semibold d-block mb-2"><?= aman_html(date('d/m/Y H:i', strtotime($pengumuman['tanggal_publikasi']))); ?></small>
                                    <h3 class="h5"><?= aman_html($pengumuman['judul']); ?></h3>
                                    <p class="text-secondary mb-0"><?= nl2br(aman_html($pengumuman['isi_pengumuman'])); ?></p>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================= FAQ ================= -->
    <section class="py-5" id="faq">
        <div class="container py-lg-4">
            <div class="text-center mx-auto batas-judul mb-4">
                <p class="teks-label">FAQ</p>
                <h2 class="judul-bagian">Pertanyaan yang sering ditanyakan</h2>
            </div>
            <div class="accordion accordion-flush mx-auto batas-faq" id="daftarFaq">
                <div class="accordion-item border rounded-3 mb-3 shadow-sm overflow-hidden">
                    <h3 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqSatu">Bagaimana cara mengambil nomor antrean?</button></h3>
                    <div id="faqSatu" class="accordion-collapse collapse show" data-bs-parent="#daftarFaq"><div class="accordion-body text-secondary">Registrasi atau login terlebih dahulu, pilih poli, lalu isi data pendaftaran untuk mendapatkan nomor antrean.</div></div>
                </div>
                <div class="accordion-item border rounded-3 mb-3 shadow-sm overflow-hidden">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqDua">Apakah saya dapat melihat status antrean?</button></h3>
                    <div id="faqDua" class="accordion-collapse collapse" data-bs-parent="#daftarFaq"><div class="accordion-body text-secondary">Ya. Setelah mengambil nomor antrean, pasien dapat melihat status antrean melalui akun masing-masing.</div></div>
                </div>
                <div class="accordion-item border rounded-3 shadow-sm overflow-hidden">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqTiga">Apakah nomor antrean dapat diambil untuk semua poli?</button></h3>
                    <div id="faqTiga" class="accordion-collapse collapse" data-bs-parent="#daftarFaq"><div class="accordion-body text-secondary">Pasien dapat memilih poli yang tersedia dan aktif pada saat mengambil nomor antrean.</div></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= AJAKAN AKSI ================= -->
    <section class="py-5">
        <div class="container">
            <div class="ajakan-antrean text-center rounded-4 p-4 p-md-5">
                <h2 class="h1 fw-bold">Siap mengambil nomor antrean?</h2>
                <p class="mb-4">Daftar sebagai pasien dan akses layanan antrean Puskesmas secara online.</p>
                <a class="btn btn-light btn-lg px-4" href="<?= aman_html(url_aplikasi('auth/registrasi.php')); ?>">Registrasi Sekarang</a>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/kaki.php'; ?>
