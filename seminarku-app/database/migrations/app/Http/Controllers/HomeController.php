<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Halaman publik: beranda, ketentuan layanan, verifikasi sertifikat. */
class HomeController extends Controller
{
    /** Beranda (landing page) dengan ringkasan angka dan 3 acara terdekat. */
    public function home()
    {
        $today = date('Y-m-d');
        $stats = [
            [Event::where('status', 'terbit')->where('event_date', '>=', $today)->count(), 'acara dibuka'],
            [User::where('role', 'penyelenggara')->where('status', 'aktif')->count(), 'panitia'],
            [Certificate::count(), 'sertifikat terbit'],
        ];
        $upcoming = Event::withStats()->where('status', 'terbit')->where('event_date', '>=', $today)
            ->orderBy('event_date')->orderBy('start_time')->limit(3)->get();

        return view('pages.home', compact('stats', 'upcoming'));
    }

    /** Ketentuan layanan (statis). */
    public function ketentuan()
    {
        $secs = [
            'akun' => ['Akun dan peran', [
                'Peserta mendaftar dengan NIM dan nama lengkap. Nama yang tertulis di akun dicetak pada sertifikat, jadi periksa penulisannya.',
                'Penyelenggara (panitia) baru dapat membuat acara setelah akunnya divalidasi administrator.',
                'Satu orang memakai satu akun. Jaga kerahasiaan kata sandi Anda.',
            ]],
            'daftar' => ['Pendaftaran dan tiket', [
                'Pendaftaran dianggap sah setelah panitia menyetujuinya. Acara gratis tertentu disetujui otomatis bila panitia memilih pengaturan itu.',
                'QR Code tiket hanya muncul setelah pendaftaran Anda disetujui. QR tidak tampil selama menunggu persetujuan, ditolak, atau setelah acara berlalu.',
                'Tiket berlaku untuk satu peserta dan satu kali masuk. Jangan membagikan QR Code Anda.',
            ]],
            'bayar' => ['Acara berbayar', [
                'Pembayaran dilakukan langsung ke penyelenggara melalui QRIS atau rekening yang tercantum pada halaman tiket. SeminarKu tidak memproses atau menyimpan uang.',
                'Unggah bukti pembayaran pada halaman tiket. Panitia memeriksa bukti tersebut lalu menyetujui atau menolak pendaftaran.',
                'Kebijakan pengembalian dana mengikuti ketentuan masing-masing penyelenggara. Hubungi panitia lewat menu Pesan.',
            ]],
            'hadir' => ['Kehadiran dan sertifikat', [
                'Kehadiran dicatat panitia dengan memindai QR Code, atau oleh peserta dengan kode presensi pada hari acara.',
                'Hanya pendaftar yang disetujui yang dapat tercatat hadir.',
                'E-sertifikat terbit otomatis untuk acara yang menyediakannya dan dapat diperiksa keasliannya di halaman Verifikasi. Keabsahan sertifikat sebagai satuan kredit tetap mengikuti keputusan program studi.',
            ]],
            'pesan' => ['Pesan antara peserta dan panitia', [
                'Fitur Pesan hanya untuk keperluan acara yang Anda ikuti atau selenggarakan. Gunakan bahasa yang sopan.',
                'Pesan bersifat privat: hanya dapat dilihat oleh peserta dan panitia acara tersebut. Administrator tidak memiliki akses ke isi pesan.',
            ]],
            'acara' => ['Penyelenggara dan acara', [
                'Periksa daftar acara yang sudah ada sebelum membuat acara baru. Sistem menolak acara pada tempat dan jam yang bentrok, dan meminta konfirmasi bila judulnya mirip acara lain.',
                'Informasi acara harus benar. Administrator dapat menutup acara yang menyesatkan.',
            ]],
            'data' => ['Data pribadi', [
                'Data Anda hanya dipakai untuk pendaftaran, presensi, sertifikat, dan komunikasi acara.',
                'Bukti pembayaran hanya dapat dilihat oleh pemilik, panitia acara terkait, dan administrator.',
            ]],
        ];

        return view('pages.ketentuan', compact('secs'));
    }

    /** Verifikasi sertifikat dari kode (?code=). Pengguna masuk melihatnya di dalam aplikasi. */
    public function verify()
    {
        $code = strtoupper(trim((string)request()->query('code', '')));
        $cert = null;
        if ($code !== '') {
            $row = DB::table('certificates as c')
                ->join('registrations as r', 'r.id', '=', 'c.registration_id')
                ->join('events as e', 'e.id', '=', 'r.event_id')
                ->join('users as o', 'o.id', '=', 'e.organizer_id')
                ->where('c.verify_code', $code)
                ->first(['c.*', 'r.attended_at', 'e.title', 'e.event_date', 'e.location', 'o.name as organizer_name']);
            $cert = $row ? (array)$row : null;
        }

        return view(user() ? 'pages.verify_app' : 'pages.verify', compact('code', 'cert'));
    }
}
