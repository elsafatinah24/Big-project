
# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# Aplikasi Tiket Antrian Online Puskesmas Berbasis Web

---

# 1. Informasi Proyek

### Nama Proyek

Aplikasi Tiket Antrian Online Puskesmas Berbasis Web

### Jenis Proyek

Big Project PKL

### Platform

Website

### Target Pengguna

* Admin
* Pasien

---

# 2. Latar Belakang

Berdasarkan hasil observasi di puskesmas, proses pelayanan masih menggunakan sistem antrean langsung. Pasien datang ke puskesmas, memilih poli sesuai kebutuhan, mengambil nomor antrean sesuai poli yang dipilih, melakukan pendaftaran dengan mengisi data diri, kemudian menunggu di ruang tunggu sesuai poli hingga nomor antreannya dipanggil oleh petugas melalui speaker.

Sistem tersebut masih mengharuskan pasien datang langsung untuk mengambil nomor antrean sehingga dapat menyebabkan waktu tunggu menjadi lebih lama ketika jumlah pasien meningkat. Oleh karena itu, dirancang aplikasi tiket antrean online berbasis web untuk membantu proses antrean menjadi lebih efektif, efisien, dan terorganisir.

---

# 3. Tujuan

* Mempermudah pasien mengambil nomor antrean secara online.
* Membantu petugas mengelola antrean.
* Mengurangi waktu tunggu pasien.
* Menyediakan informasi status antrean secara real-time.
* Meningkatkan kualitas pelayanan kepada masyarakat.

---

# 4. Ruang Lingkup

### Sistem mencakup

* Landing Page
* Login Admin dan Pasien
* Registrasi Pasien
* Pengambilan Nomor Antrean Online
* Pengelolaan Data Pasien
* Pengelolaan Data Poli
* Pengelolaan Data Antrean
* Dashboard Admin
* Laporan
* Berita/Pengumuman

### Sistem tidak mencakup

* Rekam Medis
* Diagnosa Dokter
* Pembayaran
* Pengelolaan Data Obat

---

# 5. Hasil Observasi

Alur pelayanan di Puskesmas:

1. Pasien memilih poli sesuai kebutuhan.
2. Pasien mengambil nomor antrean sesuai poli.
3. Pasien mengisi data pendaftaran:

   * Nama
   * Tanggal Lahir
   * Alamat
   * Nomor Telepon
   * Data lain yang diperlukan
4. Pasien menunggu di ruang tunggu.
5. Petugas memanggil nomor antrean melalui speaker.
6. Pasien menjalani pemeriksaan.
7. Jika memperoleh resep, pasien mengambil obat di farmasi.

---

# 6. Pengguna Sistem

## Admin

Hak akses:

* Login
* Dashboard
* Kelola Data Pasien
* Kelola Data Poli
* Kelola Data Antrean
* Memanggil Antrean
* Mengubah Status Antrean
* Kelola Berita/Pengumuman
* Melihat Laporan

## Pasien

Hak akses:

* Registrasi
* Login
* Memilih Poli
* Mengambil Nomor Antrean
* Mengisi Data Pendaftaran
* Melihat Status Antrean
* Melihat Riwayat Antrean

---

# 7. Fitur Aplikasi

## Landing Page

* Beranda
* Profil Puskesmas
* Informasi Layanan
* Jadwal Pelayanan
* Berita / Pengumuman
* FAQ
* Login
* Registrasi

## Modul Pasien

* Registrasi
* Login
* Memilih Poli
* Mengambil Nomor Antrean
* Mengisi Data Pendaftaran
* Melihat Status Antrean
* Riwayat Antrean
* Profil

## Modul Admin

* Login
* Dashboard
* Kelola Data Pasien
* Kelola Data Poli
* Kelola Data Antrean
* Memanggil Nomor Antrean
* Mengubah Status Antrean
* Kelola Berita / Pengumuman
* Laporan

---

# 8. Data yang Dikelola (CRUD)

| Data                     | Create | Read | Update | Delete |
| ------------------------ | :----: | :--: | :----: | :----: |
| Data Pasien              |   ✓   |  ✓  |   ✓   |   ✓   |
| Data Poli                |   ✓   |  ✓  |   ✓   |   ✓   |
| Data Antrean             |   ✓   |  ✓  |   ✓   |   ✓   |
| Data Berita / Pengumuman |   ✓   |  ✓  |   ✓   |   ✓   |

---

# 9. Dashboard Overview

Dashboard Admin menggunakan konsep **Modern Healthcare Dashboard** dengan dominasi warna hijau sebagai identitas visual Puskesmas. Tampilan dibuat modern, bersih, responsif, dan mudah dipahami sehingga admin dapat memantau kondisi pelayanan secara real-time.

## Tema Dashboard

* **Primary Color:** Hijau (#2E7D32)
* **Secondary Color:** Hijau Muda (#66BB6A)
* **Background:** Hijau Sangat Muda (#F1F8E9)
* **Card:** Putih (#FFFFFF) dengan border radius dan soft shadow.
* **Text:** Abu-abu Tua (#333333).

## Layout Dashboard

Dashboard menggunakan tata letak berbasis **grid layout** dan **card layout** sehingga informasi mudah dibaca pada desktop maupun perangkat mobile.

### Ringkasan Statistik

Dashboard menampilkan kartu statistik berupa:

* Total Pasien
* Total Antrean Hari Ini
* Jumlah Antrean Menunggu
* Jumlah Antrean Dipanggil
* Jumlah Antrean Selesai

Setiap kartu dilengkapi ikon dan indikator warna untuk memudahkan identifikasi informasi.

### Grafik

Dashboard menampilkan:

* Grafik Antrean Harian
* Grafik Jumlah Pasien per Poli

### Informasi Tambahan

Dashboard juga menyediakan:

* Daftar antrean yang sedang berlangsung.
* Nomor antrean yang sedang dipanggil.
* Jadwal pelayanan aktif.
* Berita atau pengumuman terbaru.

---

# 10. Security

Sistem menerapkan keamanan sebagai berikut:

* Login menggunakan autentikasi.
* Role Based Access Control (RBAC) antara Admin dan Pasien.
* Password disimpan menggunakan hashing.
* Validasi input pada setiap form.
* Session Login.
* Logout.
* Dashboard hanya dapat diakses sesuai hak akses pengguna.

---

# 11. Kebutuhan Teknologi

## Frontend

* HTML
* CSS
* JavaScript

## Backend

* PHP Native

## Database

* MySQL

---

# 12. Desain Antarmuka (UI/UX)

Aplikasi menggunakan konsep **Modern Healthcare UI** dengan tampilan bersih, responsif, profesional, dan mudah digunakan oleh seluruh kalangan masyarakat.

## Tema Warna

Aplikasi menggunakan identitas visual khas Puskesmas dengan dominasi warna hijau.

### Palet Warna

* **Primary:** Hijau (#2E7D32)
* **Secondary:** Hijau Muda (#66BB6A)
* **Background:** Hijau Sangat Muda (#F1F8E9)
* **Surface/Card:** Putih (#FFFFFF)
* **Text:** Abu-abu Tua (#333333)
* **Success:** Hijau (#4CAF50)
* **Warning:** Oranye (#FFA726)
* **Danger:** Merah (#E53935)

## Konsep Desain

* Modern dan minimalis.
* Clean layout.
* Responsive (Desktop, Tablet, Mobile).
* Card layout.
* Border radius 12–16 px.
* Soft shadow.
* Ikon modern.
* Animasi ringan (hover, fade, transition).
* Font menggunakan **Poppins** atau **Inter**.

## Landing Page

Landing page terdiri dari:

* Hero Section dengan ilustrasi pelayanan kesehatan.
* Profil Puskesmas.
* Informasi Layanan.
* Jadwal Pelayanan.
* Berita/Pengumuman.
* FAQ.
* Call To Action **"Ambil Nomor Antrean"**.

## Dashboard Admin

Menggunakan desain modern dengan dominasi warna hijau dan putih yang konsisten.

## Modul Pasien

Dirancang sederhana agar mudah digunakan untuk:

* Memilih Poli.
* Mengambil Nomor Antrean.
* Mengisi Data Pendaftaran.
* Melihat Status Antrean secara real-time.
* Melihat Riwayat Antrean.
* Mengelola Profil.

---

# 13. Standar Penulisan Kode (Coding Standard)

Untuk memudahkan maintenance dan debugging, setiap modul wajib menggunakan komentar.

Contoh:

```php
// ================= MODUL LOGIN =================

// Validasi Login

// Autentikasi Pengguna

// ================= MODUL DATA PASIEN =================

// CRUD Data Pasien

// ================= MODUL DATA POLI =================

// CRUD Data Poli

// ================= MODUL ANTREAN =================

// Mengambil Nomor Antrean

// Mengubah Status Antrean

// ================= DASHBOARD =================

// Menampilkan Statistik Dashboard
```

---

# 14. Target Hasil

Aplikasi yang dikembangkan diharapkan memenuhi kualifikasi Big Project PKL, yaitu:

* Landing Page modern dengan konten dinamis.
* UI/UX bertema hijau khas Puskesmas.
* Autentikasi Admin dan Pasien.
* CRUD pada seluruh data utama.
* Dashboard Overview modern dan informatif.
* Grafik statistik antrean.
* Sistem antrean online yang berjalan secara real-time.
* Penerapan keamanan (RBAC, Hash Password, Validasi Input, Session Login).
* Tampilan responsif pada desktop, tablet, dan smartphone.
* Source code terstruktur, terdokumentasi, dan mudah dipelihara (maintenance).
