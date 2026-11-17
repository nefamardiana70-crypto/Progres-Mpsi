<?php

/**
 * Data contoh (demo). Tanggal acara bersifat RELATIF terhadap hari ini supaya data selalu "segar".
 * Dipakai oleh seed_demo() (SQLite/MySQL otomatis) dan database/make_sql.php (membuat database.sql).
 * Urutan array = ID (mulai dari 1).
 */
return [
    // nama, email, sandi, peran, nim, institusi, telepon, status
    'users' => [
        ['Administrator Sistem',             'admin@seminar.test',   'admin123',   'admin',         null,         'UIN Imam Bonjol Padang', null,           'aktif'],      // 1
        ['BEM Fakultas Sains dan Teknologi', 'panitia@seminar.test', 'panitia123', 'penyelenggara', null,         'UIN Imam Bonjol Padang', '081200000001', 'aktif'],      // 2
        ['HMJ Sistem Informasi',             'hmj@seminar.test',     'hmj12345',   'penyelenggara', null,         'UIN Imam Bonjol Padang', '081200000002', 'aktif'],      // 3
        ['Nefa Mardiana',                    'mhs@seminar.test',     'mhs12345',   'peserta',       '2417020103', 'UIN Imam Bonjol Padang', '081200000003', 'aktif'],      // 4
        ['Rachmad Fajar Arisyi',             'mhs2@seminar.test',    'mhs12345',   'peserta',       '2417020116', 'UIN Imam Bonjol Padang', '081200000004', 'aktif'],      // 5
        ['Andika Namora Bayo Lubis',         'mhs3@seminar.test',    'mhs12345',   'peserta',       '2417020079', 'UIN Imam Bonjol Padang', '081200000005', 'aktif'],      // 6
        ['Sissylia Amanda Putri',            'mhs4@seminar.test',    'mhs12345',   'peserta',       '2417020124', 'UIN Imam Bonjol Padang', '081200000006', 'aktif'],      // 7
        ['UKM Penalaran Mahasiswa',          'ukm@seminar.test',     'ukm12345',   'penyelenggara', null,         'UIN Imam Bonjol Padang', '081200000007', 'menunggu'],   // 8 (antre validasi)
    ],

    // organizer(id user), judul, deskripsi, kategori, selisih hari, jam, lokasi, mode, biaya, kuota, sertifikat, status, persetujuan, petunjuk bayar
    'events' => [
        [2, 'Workshop Dasar Pemrograman Web dengan PHP', 'Pelajari dasar PHP, koneksi basis data, dan membangun aplikasi web sederhana dari nol bersama praktisi.', 'Teknologi', 3, '09:00', 'Zoom Meeting', 'online', 0, 100, 1, 'terbit', 'auto', null],                    // 1
        [2, 'Seminar Nasional Transformasi Digital', 'Membahas arah transformasi digital di perguruan tinggi, kecerdasan buatan, dan kesiapan lulusan menghadapi dunia kerja.', 'Teknologi', 6, '08:30', 'Aula Utama UIN Imam Bonjol Padang', 'offline', 0, 200, 1, 'terbit', 'manual', null], // 2
        [2, 'Pelatihan Penulisan Skripsi dan Jurnal Ilmiah', 'Teknik menyusun kerangka skripsi, sitasi, serta strategi publikasi jurnal bagi mahasiswa tingkat akhir.', 'Pendidikan', 9, '13:00', 'Ruang Seminar Fakultas Sains dan Teknologi', 'offline', 25000, 40, 1, 'terbit', 'manual', 'Transfer ke Bank BSI 7123456789 a.n. Bendahara BEM FST. Cantumkan nama dan judul acara pada berita transfer.'], // 3
        [3, 'Webinar Kewirausahaan Mahasiswa', 'Berbagi pengalaman memulai usaha rintisan dari kampus: validasi ide, pendanaan, dan membangun tim.', 'Bisnis', 2, '19:30', 'Google Meet', 'online', 0, 150, 1, 'terbit', 'manual', null],                                           // 4
        [3, 'Seminar Kesehatan Mental Mahasiswa', 'Mengenali stres akademik, cara mengelola kecemasan menjelang sidang, dan layanan konseling kampus.', 'Kesehatan', 12, '09:00', 'Gedung Serbaguna Kampus 2', 'offline', 0, 80, 1, 'terbit', 'manual', null],                     // 5
        [3, 'Pelatihan Analisis Data dengan Microsoft Excel', 'Praktik pengolahan data, tabel pivot, dan visualisasi untuk kebutuhan penelitian dan laporan akhir.', 'Sains', 15, '10:00', 'Zoom Meeting', 'online', 50000, 30, 1, 'terbit', 'manual', 'Bayar via QRIS atau transfer Bank BSI 7198765432 a.n. Bendahara HMJ SI, lalu unggah bukti pembayaran.'], // 6
        [2, 'Diskusi Panel Karier Lulusan Sistem Informasi', 'Diskusi santai bersama alumni mengenai peluang karier. Acara ini tidak menerbitkan sertifikat.', 'Pengembangan Diri', 8, '15:00', 'Ruang Diskusi Perpustakaan', 'offline', 0, 60, 0, 'terbit', 'manual', null], // 7
        [3, 'Pelatihan UI/UX Dasar untuk Pemula', 'Mengenal prinsip desain antarmuka, wireframe, dan prototipe menggunakan Figma.', 'Teknologi', 5, '09:00', 'Lab Komputer 3', 'offline', 0, 1, 1, 'terbit', 'manual', null],                                      // 8 (kuota 1, penuh)
        [2, 'Workshop Public Speaking untuk Mahasiswa', 'Latihan berbicara di depan umum, mengatasi gugup, dan menyiapkan presentasi sidang.', 'Pengembangan Diri', 20, '09:00', 'Aula Fakultas', 'offline', 15000, 25, 1, 'terbit', 'manual', 'Transfer ke Bank BSI 7123456789 a.n. Bendahara BEM FST, lalu unggah bukti pembayaran.'], // 9
        [2, 'Seminar Literasi Digital', 'Seminar tentang keamanan data pribadi dan etika bermedia digital. Acara ini sudah terlaksana.', 'Pendidikan', -10, '09:00', 'Aula Utama UIN Imam Bonjol Padang', 'offline', 0, 120, 1, 'terbit', 'manual', null],        // 10 (sudah terlaksana)
        [3, 'Lokakarya Desain Poster Digital', 'Praktik membuat poster kegiatan kampus dengan aplikasi desain gratis. Acara ini sudah terlaksana.', 'Teknologi', -4, '13:00', 'Lab Komputer 2', 'offline', 0, 30, 1, 'terbit', 'manual', null],             // 11 (sudah terlaksana)
        [2, 'Pelatihan Keamanan Siber (draf)', 'Masih berupa draf dan belum dipublikasikan.', 'Teknologi', 30, '09:00', 'Zoom Meeting', 'online', 0, 50, 1, 'draft', 'manual', null],                                                                       // 12
    ],

    // event, peserta, status, bukti(bool), hadir(bool), alasan tolak, selisih hari pendaftaran
    'regs' => [
        [1,  4, 'disetujui', false, false, null, -1],
        [4,  4, 'disetujui', false, false, null, -1],
        [2,  4, 'menunggu',  false, false, null, 0],
        [3,  5, 'menunggu',  true,  false, null, 0],     // berbayar, bukti sudah diunggah -> menunggu verifikasi
        [6,  4, 'menunggu',  false, false, null, 0],     // berbayar, belum unggah bukti
        [8,  5, 'disetujui', false, false, null, -2],    // mengisi kuota acara #8
        [5,  5, 'ditolak',   false, false, 'Kuota prioritas untuk mahasiswa semester akhir.', -1],
        [10, 4, 'disetujui', false, true,  null, -14],
        [10, 5, 'disetujui', false, false, null, -14],   // tidak hadir
        [11, 5, 'disetujui', false, true,  null, -8],
        [7,  6, 'disetujui', false, false, null, -1],
        [2,  6, 'disetujui', false, false, null, -2],
        [2,  7, 'menunggu',  false, false, null, 0],
    ],

    // event, peserta, pengirim, isi, menit yang lalu, sudah dibaca
    'messages' => [
        [3, 5, 5, 'Selamat siang panitia, apakah pembayaran boleh lewat QRIS?', 95, true],
        [3, 5, 2, 'Siang. Boleh, silakan unggah bukti pembayarannya di halaman tiket ya.', 80, true],
        [3, 5, 5, 'Baik, buktinya sudah saya unggah. Mohon diverifikasi.', 20, false],
        [2, 4, 4, 'Apakah acara ini tetap berlangsung offline?', 300, false],
    ],
];
