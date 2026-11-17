<?php

/*
 * Peta rute SeminarKu. Dipisah per kelompok supaya mudah dibaca:
 *   auth.php         masuk, daftar, keluar
 *   participant.php  halaman publik + fitur peserta (acara, tiket, sertifikat, profil, verifikasi)
 *   organizer.php    fitur panitia (acara, peserta, presensi, jelajah)
 *   admin.php        beranda/kalender, kelola pengguna, laporan, pesan, penyaji berkas
 */
require __DIR__ . '/auth.php';
require __DIR__ . '/participant.php';
require __DIR__ . '/organizer.php';
require __DIR__ . '/admin.php';
