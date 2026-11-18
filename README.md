# Progres-Mpsi
setiap minggu harus ada progresnya
# SeminarKu — Sistem Informasi Manajemen Seminar & E-Sertifikat

Aplikasi web berbasis Laravel untuk mengelola penyelenggaraan acara seminar, verifikasi kehadiran peserta menggunakan pemindaian QR Code, komunikasi peserta dengan penyelenggara, serta penerbitan e-sertifikat otomatis dalam format PDF.

Proyek ini dikembangkan sebagai tugas kelompok pada mata kuliah **Manajemen Proyek Sistem Informasi (MPSI)**, Program Studi Sistem Informasi, UIN Imam Bonjol Padang.

---

## Fitur Utama

* **Peserta (Participant):** Pendaftaran seminar daring, tiket elektronik dengan QR Code unik, unduh e-sertifikat PDF otomatis setelah presensi terverifikasi, serta fitur pesan (*chat*) dengan penyelenggara.
* **Penyelenggara (Organizer):** Pembuatan dan pengelolaan jadwal acara, pemindaian (*scan*) QR Code kehadiran peserta secara langsung, serta rekap data peserta.
* **Administrator:** Pengelolaan akun pengguna dan pemantauan laporan kegiatan seminar.
* **Verifikasi Publik:** Pengecekan keaslian e-sertifikat secara daring melalui halaman verifikasi.

## Teknologi yang Digunakan

* **Backend:** PHP 8.2+, Laravel Framework
* **Database:** SQLite
* **Frontend & Utilitas:** Blade Template, CSS/JavaScript, `html5-qrcode`, `qrcode.js`
* **Server Lokal:** Laragon (Apache) / PHP Built-in Server

## Tim Pengembang

* **Rachmad Fajar Arisyi** ([@fajararisyi](https://github.com/fajararisyi))
* **Nefa Mardiana** ([@nefamardiana70-crypto](https://github.com/nefamardiana70-crypto))
* **Sissylia Amanda Putri** ([@SissyliaAmandaPutri](https://github.com/SissyliaAmandaPutri))

## Cara Menjalankan Proyek Secara Lokal

1. Masuk ke direktori aplikasi utama:
   ```bash
   cd seminarku-app