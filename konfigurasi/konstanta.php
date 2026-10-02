<?php
// ================= KONFIGURASI APLIKASI =================
// Ubah URL_DASAR sesuai nama folder aplikasi pada web server lokal.

declare(strict_types=1);

const NAMA_APLIKASI = 'Aplikasi Tiket Antrean Online Puskesmas';
const URL_DASAR = '/Antrian_online_puskesmas';
const ZONA_WAKTU = 'Asia/Jakarta';

date_default_timezone_set(ZONA_WAKTU);
