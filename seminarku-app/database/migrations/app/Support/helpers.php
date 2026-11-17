<?php
/*
 * Fungsi bantu SeminarKu (dimuat oleh AppServiceProvider).
 * Sengaja tanpa declare(strict_types) karena model Eloquent dan Carbon sering dipakai sebagai nilai teks.
 * Model Eloquent mendukung akses seperti array ($ev['title']), sehingga fungsi di sini menerima model maupun array.
 */

use App\Models\Event;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

if (!defined('APP_NAME')) {
    define('APP_NAME', 'SeminarKu');
    define('CATEGORIES', ['Teknologi', 'Bisnis', 'Pendidikan', 'Kesehatan', 'Sains', 'Pengembangan Diri']);
    define('BULAN', ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']);
    define('HARI', ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']);
}

/* =====================================================================
 *  Umum
 * ===================================================================== */

/** Pengguna yang sedang masuk (model User) atau null. */
function user()
{
    return auth()->user();
}

/** Simpan pesan kilat. Jenis: ok | error | warn | info. Ditampilkan oleh layout pada permintaan berikutnya. */
function flash(string $type, string $msg): void
{
    session()->push('flash', [$type, $msg]);
}

/** Ambil dan hapus pesan kilat. */
function take_flashes(): array
{
    return session()->pull('flash', []);
}

/* =====================================================================
 *  Format
 * ===================================================================== */
function tgl(?string $ymd, bool $long = false): string
{
    if (!$ymd) {
        return '-';
    }
    $t = strtotime((string)$ymd);
    $m = (int)date('n', $t);
    return date('j', $t) . ' ' . ($long ? BULAN[$m] : mb_substr(BULAN[$m], 0, 3)) . ' ' . date('Y', $t);
}

function rupiah($n): string
{
    $n = (int)$n;
    return $n <= 0 ? 'Gratis' : 'Rp ' . number_format($n, 0, ',', '.');
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $s = mb_substr($parts[0] ?? '?', 0, 1);
    if (count($parts) > 1) {
        $s .= mb_substr($parts[1], 0, 1);
    }
    return mb_strtoupper($s);
}

function role_label(string $r): string
{
    return ['peserta' => 'Peserta', 'penyelenggara' => 'Penyelenggara', 'admin' => 'Administrator'][$r] ?? $r;
}

/** Waktu relatif singkat: "baru", "5 mnt", "3 j", "2 h", atau tanggal. */
function ago($datetime): string
{
    $ts = strtotime((string)$datetime);
    $d = time() - $ts;
    if ($d < 60) return 'baru';
    if ($d < 3600) return floor($d / 60) . ' mnt';
    if ($d < 86400) return floor($d / 3600) . ' j';
    if ($d < 7 * 86400) return floor($d / 86400) . ' h';
    return date('j', $ts) . ' ' . mb_substr(BULAN[(int)date('n', $ts)], 0, 3);
}

function badge(string $text, string $cls = ''): string
{
    return '<span class="badge ' . e($cls) . '">' . e($text) . '</span>';
}

/* =====================================================================
 *  Acara
 * ===================================================================== */

/** Satu acara lengkap dengan penyelenggara + hitungan registered/pending, atau null. */
function get_event(int $id): ?Event
{
    return Event::withStats()->find($id);
}

function seats_left($ev): int
{
    return max(0, (int)$ev['quota'] - (int)$ev['registered']);
}

function event_done($ev): bool
{
    return $ev['status'] !== 'draft' && (string)$ev['event_date'] < date('Y-m-d');
}

/** Apakah pendaftaran masih dibuka? Mengembalikan [bool, alasan]. */
function registration_open($ev): array
{
    $today = date('Y-m-d');
    if ($ev['status'] !== 'terbit') {
        return [false, 'Pendaftaran ditutup'];
    }
    if ((string)$ev['event_date'] < $today || ($ev['reg_deadline'] && (string)$ev['reg_deadline'] < $today)) {
        return [false, 'Pendaftaran sudah berakhir'];
    }
    if (seats_left($ev) <= 0) {
        return [false, 'Kuota penuh'];
    }
    return [true, ''];
}

function ticket_code(): string
{
    return 'TKT-' . strtoupper(bin2hex(random_bytes(5)));
}

/**
 * Mencegah acara ganda: kembalikan acara lain (non-draf) yang
 *  - 'venue': memakai lokasi offline yang sama pada tanggal sama dan jam berdekatan (< 3 jam), atau
 *  - 'dup'  : berjudul sangat mirip dalam rentang +-45 hari.
 * $new = array berisi title, location, mode, event_date, start_time.
 */
function find_event_conflicts(array $new, ?int $excludeId = null): array
{
    $out = ['dup' => null, 'venue' => null];
    $others = Event::with('organizer')->where('status', '<>', 'draft')->where('id', '<>', (int)$excludeId)->get();
    $norm = fn(string $s): string => trim((string)preg_replace('/\s+/', ' ', mb_strtolower($s)));
    $mins = fn(string $t): int => ((int)substr($t, 0, 2)) * 60 + (int)substr($t, 3, 2);
    $nt = $norm($new['title']);
    $nl = $norm($new['location']);
    $best = 0.0;
    foreach ($others as $o) {
        if ($out['venue'] === null && $new['mode'] === 'offline' && $o['mode'] === 'offline' && (string)$o['event_date'] === $new['event_date']
            && $norm($o['location']) === $nl && abs($mins($o['start_time']) - $mins($new['start_time'])) < 180) {
            $out['venue'] = $o;
        }
        $days = abs((strtotime((string)$o['event_date']) - strtotime($new['event_date'])) / 86400);
        if ($days <= 45) {
            similar_text($nt, $norm($o['title']), $pct);
            if ($pct >= 75 && $pct > $best) {
                $best = $pct;
                $out['dup'] = $o;
            }
        }
    }
    return $out;
}

/* =====================================================================
 *  Pendaftaran, tiket, kehadiran
 * ===================================================================== */

/** [label, kelas badge] status pendaftaran untuk tampilan. */
function reg_state($r, $ev): array
{
    if (($r['status'] ?? '') === 'ditolak') {
        return ['Ditolak', 'badge-err'];
    }
    if (!empty($r['attended'])) {
        return ['Hadir', 'badge-ok'];
    }
    if (($r['status'] ?? '') === 'disetujui') {
        return event_done($ev) ? ['Tidak hadir', 'badge-gray'] : ['Disetujui', 'badge-ok'];
    }
    if ((int)$ev['fee'] > 0) {
        return empty($r['payment_proof']) ? ['Menunggu bukti bayar', 'badge-warn'] : ['Menunggu verifikasi', 'badge-warn'];
    }
    return ['Menunggu persetujuan', 'badge-warn'];
}

/** QR tiket hanya boleh tampil bila panitia sudah menyetujui, acara sudah terbit, dan acara belum lewat. */
function ticket_qr_visible($reg, $ev): bool
{
    return $reg['status'] === 'disetujui' && !$reg['attended'] && $ev['status'] !== 'draft' && (string)$ev['event_date'] >= date('Y-m-d');
}

/**
 * Tandai peserta hadir. Hanya pendaftar yang DISETUJUI. Jika acara menerbitkan sertifikat,
 * sertifikat dibuat otomatis (nama dari profil peserta). Mengembalikan kode verifikasi atau null.
 */
function mark_attended(int $regId): ?string
{
    return DB::transaction(function () use ($regId) {
        $r = \App\Models\Registration::with(['event', 'user'])->lockForUpdate()->find($regId);
        if (!$r || $r->status !== 'disetujui') {
            return null;
        }
        if (!$r->attended) {
            $r->update(['attended' => true, 'attended_at' => date('Y-m-d H:i:s')]);
        }
        $code = null;
        if ($r->event->has_certificate) {
            $cert = \App\Models\Certificate::where('registration_id', $r->id)->first();
            if (!$cert) {
                $cert = \App\Models\Certificate::create([
                    'registration_id' => $r->id,
                    'verify_code' => 'CERT-' . strtoupper(bin2hex(random_bytes(5))),
                    'holder_name' => $r->user->name,
                    'issued_at' => date('Y-m-d H:i:s'),
                ]);
            }
            $code = $cert->verify_code;
        }
        return $code;
    });
}

/* =====================================================================
 *  Unggahan gambar (poster, QRIS, bukti bayar)
 *  Disimpan di storage/app/.../uploads/<jenis>/<acak>.<ext>, hanya dilayani lewat route 'file'.
 * ===================================================================== */
function upload_valid_name(?string $rel): bool
{
    return $rel !== null && (bool)preg_match('#^(qris|proof|poster)/[a-f0-9]{32}\.(jpg|png|webp)$#', $rel);
}

/** Lokasi berkas di disk (untuk dibaca), atau null bila tidak valid / tidak ada. */
function upload_path(?string $rel): ?string
{
    if (!upload_valid_name($rel)) {
        return null;
    }
    $p = Storage::disk('local')->path('uploads/' . $rel);
    return is_file($p) ? $p : null;
}

/**
 * Simpan satu unggahan gambar dari request. Hasil: [] (tidak ada berkas), ['file' => 'qris/xxx.png'] atau ['error' => 'pesan'].
 */
function save_image_upload(string $field, string $kind): array
{
    if (!in_array($kind, ['qris', 'proof', 'poster'], true)) {
        return ['error' => 'Jenis berkas tidak valid.'];
    }
    $f = request()->file($field);
    if (!$f) {
        return [];
    }
    $maxMb = $kind === 'poster' ? 4 : 2;
    if (!$f->isValid()) {
        $err = $f->getError();
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            return ['error' => 'Ukuran gambar maksimal ' . $maxMb . ' MB (periksa juga upload_max_filesize pada php.ini).'];
        }
        return ['error' => 'Unggahan gagal. Silakan coba lagi.'];
    }
    if ($f->getSize() > $maxMb * 1024 * 1024) {
        return ['error' => 'Ukuran gambar maksimal ' . $maxMb . ' MB.'];
    }
    $info = @getimagesize($f->getPathname());
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
    if (!$info || !$ext) {
        return ['error' => 'Format gambar harus JPG, PNG, atau WebP.'];
    }
    if ($info[0] > 6000 || $info[1] > 6000) {
        return ['error' => 'Dimensi gambar terlalu besar (maksimal 6000 piksel).'];
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    Storage::disk('local')->putFileAs('uploads/' . $kind, $f, $name);
    return ['file' => $kind . '/' . $name];
}

function delete_upload(?string $rel): void
{
    if (upload_valid_name($rel)) {
        Storage::disk('local')->delete('uploads/' . $rel);
    }
}

/** URL gambar unggahan lewat route 'file' (jenis: poster | qris | proof). */
function upload_url(string $type, $id): string
{
    return route('file', ['type' => $type, 'id' => (int)$id]);
}

/* =====================================================================
 *  Pesan (chat panitia <-> peserta, per acara)
 * ===================================================================== */

/**
 * Konteks percakapan yang boleh diakses pengguna: [acara, id peserta] atau null.
 *  - peserta   : lawan bicara = panitia acara; acara tidak boleh draf.
 *  - panitia   : hanya acara miliknya; $withId = peserta yang mendaftar atau pernah menulis.
 *  - admin     : tidak punya akses (chat bersifat privat).
 */
function chat_context($u, int $eventId, int $withId): ?array
{
    $ev = get_event($eventId);
    if (!$ev) {
        return null;
    }
    if ($u['role'] === 'peserta') {
        return $ev['status'] === 'draft' ? null : [$ev, (int)$u['id']];
    }
    $isOwner = $u['role'] === 'penyelenggara' && (int)$ev['organizer_id'] === (int)$u['id'];
    if ($isOwner && $withId > 0) {
        $has = \App\Models\Registration::where('event_id', $eventId)->where('user_id', $withId)->exists()
            || Message::where('event_id', $eventId)->where('participant_id', $withId)->exists();
        return $has ? [$ev, $withId] : null;
    }
    return null;
}

/** Kirim pesan. Mengembalikan model Message atau null bila kosong / > 1000 karakter. */
function chat_send(int $eventId, int $participantId, int $senderId, string $body): ?Message
{
    $body = trim($body);
    $len = mb_strlen($body);
    if ($len < 1 || $len > 1000) {
        return null;
    }
    return Message::create([
        'event_id' => $eventId,
        'participant_id' => $participantId,
        'sender_id' => $senderId,
        'body' => $body,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

/** Jumlah pesan belum dibaca untuk pengguna (admin selalu 0: chat privat). */
function unread_messages(int $userId): int
{
    $role = DB::table('users')->where('id', $userId)->value('role');
    if ($role === 'admin') {
        return 0;
    }
    $a = Message::where('participant_id', $userId)->where('sender_id', '<>', $userId)->whereNull('read_at')->count();
    $b = Message::whereIn('event_id', DB::table('events')->where('organizer_id', $userId)->select('id'))
        ->where('sender_id', '<>', $userId)->whereNull('read_at')->count();
    return $a + $b;
}

/* =====================================================================
 *  Navigasi dan pemberitahuan
 * ===================================================================== */

/** Menu navigasi samping per peran: [kunci, label, tautan, ikon]. */
function nav_items(string $role): array
{
    if ($role === 'penyelenggara') {
        return [
            ['dashboard', 'Beranda',  route('dashboard'),         'home'],
            ['org_events', 'Acara',   route('org.events'),        'list'],
            ['org_new',   'Buat',     route('org.event.create'),  'plus'],
            ['org_browse', 'Jelajah', route('org.browse'),        'compass'],
            ['org_scan',  'Presensi', route('org.scan'),          'scan'],
            ['chat',      'Pesan',    route('chat.index'),        'chat'],
        ];
    }
    if ($role === 'admin') {
        return [
            ['dashboard',   'Beranda',  route('dashboard'),       'home'],
            ['org_events',  'Acara',    route('org.events'),      'list'],
            ['admin_users', 'Pengguna', route('admin.users'),     'users'],
            ['admin_reports', 'Laporan', route('admin.reports'),  'chart'],
            ['org_scan',    'Presensi', route('org.scan'),        'scan'],
            ['verify',      'Verifikasi', route('verify'),        'shield'],
        ];
    }
    return [
        ['dashboard', 'Beranda',   route('dashboard'),     'home'],
        ['events',    'Cari',      route('events.index'),  'search'],
        ['tickets',   'Tiket',     route('tickets.index'), 'ticket'],
        ['chat',      'Pesan',     route('chat.index'),    'chat'],
        ['certs',     'Sertifikat', route('certs.index'),  'award'],
        ['verify',    'Verifikasi', route('verify'),       'shield'],
    ];
}

/** Daftar [teks, tautan] hal yang perlu perhatian pengguna (lonceng). */
function notif_items($u): array
{
    $today = date('Y-m-d');
    $out = [];
    if ($u['role'] === 'peserta') {
        $rows = DB::table('registrations as r')->join('events as e', 'e.id', '=', 'r.event_id')
            ->where('r.user_id', $u['id'])->where('e.event_date', '>=', $today)->orderBy('e.event_date')
            ->get(['r.id as rid', 'r.status', 'r.payment_proof', 'r.attended', 'e.title', 'e.fee', 'e.event_date']);
        foreach ($rows as $r) {
            $href = route('tickets.show', $r->rid);
            if ($r->status === 'menunggu' && (int)$r->fee > 0 && !$r->payment_proof) {
                $out[] = ['Unggah bukti pembayaran: ' . $r->title, $href];
            } elseif ($r->status === 'ditolak') {
                $out[] = ['Pendaftaran ditolak: ' . $r->title, $href];
            } elseif ($r->status === 'disetujui' && !$r->attended && $r->event_date <= date('Y-m-d', strtotime('+3 days'))) {
                $out[] = [$r->title . ' dimulai ' . ($r->event_date === $today ? 'hari ini' : tgl($r->event_date)), $href];
            }
        }
    } elseif ($u['role'] === 'penyelenggara') {
        $events = Event::where('organizer_id', $u['id'])->where('status', '<>', 'draft')->orderBy('event_date')
            ->withCount(['registrations as pend' => fn($q) => $q->where('status', 'menunggu')])->get();
        foreach ($events as $ev) {
            if ((int)$ev->pend > 0) {
                $out[] = [$ev->pend . ' pendaftar menunggu keputusan: ' . $ev->title, route('org.participants', $ev->id)];
            }
        }
    } else {
        $n = DB::table('users')->where('role', 'penyelenggara')->where('status', 'menunggu')->count();
        if ($n > 0) {
            $out[] = [$n . ' penyelenggara menunggu validasi', route('admin.users', ['status' => 'menunggu'])];
        }
    }
    return $out;
}

/* =====================================================================
 *  Komponen tampilan
 * ===================================================================== */

/** Logo: lingkaran maroon dengan huruf S dan titik hijau army. */
function brand_mark(int $size = 40): string
{
    return '<svg class="brand-mark" width="' . $size . '" height="' . $size . '" viewBox="0 0 48 48" aria-hidden="true">'
        . '<defs><linearGradient id="bm" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#9a2a3c"/><stop offset="1" stop-color="#5b1020"/></linearGradient></defs>'
        . '<rect x="2" y="2" width="44" height="44" rx="14" fill="url(#bm)"/>'
        . '<path d="M30.5 16.5c-1.6-1.9-4-2.8-6.6-2.8-4 0-6.7 2-6.7 5.2 0 6.5 13.6 3.6 13.6 10.6 0 3.2-2.9 5.3-7 5.3-2.9 0-5.5-1.1-7.3-3.3" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round"/>'
        . '<circle cx="36" cy="12" r="4" fill="#8fa95a"/></svg>';
}

function icon(string $name, int $size = 20): string
{
    static $p = [
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'home'     => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'ticket'   => '<path d="M3 9a2 2 0 0 0 0 6v3a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-3a2 2 0 0 1 0-6V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1z"/><path d="M13 5v14" stroke-dasharray="2 2"/>',
        'award'    => '<circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 7 5-3 5 3-1.5-7"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-7 8-7s8 3 8 7"/>',
        'settings' => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'bell'     => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
        'scan'     => '<path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M22 20c0-2.5-1.5-4.5-4-5.5"/>',
        'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'list'     => '<path d="M3 6h18M3 12h18M3 18h12"/>',
        'shield'   => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'right'    => '<path d="m9 6 6 6-6 6"/>',
        'left'     => '<path d="m15 6-6 6 6 6"/>',
        'down'     => '<path d="m6 9 6 6 6-6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'download' => '<path d="M12 3v12m0 0-4-4m4 4 4-4M4 21h16"/>',
        'upload'   => '<path d="M12 15V3m0 0L8 7m4-4 4 4M4 21h16"/>',
        'pin'      => '<path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'check'    => '<path d="m5 12 5 5 9-10"/>',
        'x'        => '<path d="M6 6l12 12M18 6 6 18"/>',
        'chat'     => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path d="M8.5 11h7M8.5 14h4"/>',
        'send'     => '<path d="M21 3 10 14"/><path d="m21 3-7 18-4-7-7-4z"/>',
        'compass'  => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
        'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon'     => '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>',
        'lock'     => '<rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'wallet'   => '<path d="M3 7a2 2 0 0 1 2-2h13v4"/><path d="M3 7v11a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-9a1 1 0 0 0-1-1H5a2 2 0 0 1-2-2z"/><circle cx="16" cy="14.5" r="1.2"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h0"/>',
        'qr'       => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><path d="M14 14h3v3h-3zM20 14v0M14 20h3M20 17v4"/>',
    ];
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($p[$name] ?? '') . '</svg>';
}

/** Deretan angka ringkas. $rows = [[nilai, label], ...] */
function stat_tiles(array $rows): string
{
    $h = '<div class="stats">';
    foreach ($rows as $i => [$val, $label]) {
        $h .= '<div class="stat tone-' . ($i % 2 ? 'army' : 'maroon') . '"><b>' . e((string)$val) . '</b><span>' . e($label) . '</span></div>';
    }
    return $h . '</div>';
}

/**
 * Kartu acara: blok tanggal + isi. $badge dan $footer = HTML yang sudah aman.
 */
function event_card($ev, string $href, int $i, string $badge = '', string $footer = ''): string
{
    $t = strtotime((string)$ev['event_date']);
    $tone = ((int)$ev['id'] % 2) ? 'maroon' : 'army';
    $desc = mb_strimwidth((string)$ev['description'], 0, 130, '…');
    if ($footer === '') {
        $footer = 'Diselenggarakan oleh <b>' . e($ev['organizer_name']) . '</b>';
    }
    return '<a class="ecard tone-' . $tone . '" href="' . e($href) . '">'
        . '<div class="edate"><b>' . date('j', $t) . '</b><span>' . e(mb_strtoupper(mb_substr(BULAN[(int)date('n', $t)], 0, 3))) . '</span><i>' . date('Y', $t) . '</i></div>'
        . '<div class="ebody">'
        . '<div class="etop"><span class="eyebrow">' . e($ev['category']) . '</span>' . $badge . '</div>'
        . '<h3>' . e($ev['title']) . '</h3>'
        . '<p>' . e($desc) . '</p>'
        . '<div class="chips"><span class="chip">' . icon('clock', 13) . e($ev['start_time']) . ' WIB</span>'
        . '<span class="chip">' . icon('pin', 13) . e(ucfirst($ev['mode'])) . '</span>'
        . '<span class="chip">' . icon('wallet', 13) . e(rupiah((int)$ev['fee'])) . '</span></div>'
        . '<div class="efoot">' . $footer . '</div>'
        . '</div>'
        . (!empty($ev['poster']) ? '<div class="eposter"><img loading="lazy" src="' . e(upload_url('poster', $ev['id'])) . '" alt="Poster ' . e($ev['title']) . '"></div>' : '')
        . '<span class="go">' . icon('right', 18) . '</span>'
        . '</a>';
}

/** Kotak pilihan bentuk "pill" yang otomatis mengirim form saat diubah. */
function filter_select(string $name, string $label, array $options, string $current): string
{
    $h = '<label class="pill"><span class="sr">' . e($label) . '</span><select name="' . e($name) . '" data-autosubmit>';
    $h .= '<option value="">' . e($label) . '</option>';
    foreach ($options as $val => $text) {
        $h .= '<option value="' . e((string)$val) . '"' . ((string)$val === $current && $current !== '' ? ' selected' : '') . '>' . e($text) . '</option>';
    }
    return $h . '</select></label>';
}

/** Sakelar terang / malam. */
function theme_toggle(): string
{
    return '<div class="theme-toggle" role="group" aria-label="Tema tampilan">'
        . '<button type="button" data-theme-set="dark" aria-label="Mode malam" title="Mode malam">' . icon('moon', 16) . '</button>'
        . '<button type="button" data-theme-set="light" aria-label="Mode terang" title="Mode terang">' . icon('sun', 16) . '</button></div>';
}

/** Panel kiri halaman masuk/daftar: lengkung maroon, lingkaran hijau army, ilustrasi tiket dan QR. */
function auth_art(string $title, string $text): string
{
    ob_start(); ?>
<div class="auth-art">
    <a class="pub-brand" href="<?= e(route('home')) ?>"><?= brand_mark(38) ?><span><?= APP_NAME ?></span></a>
    <svg class="art-ico" viewBox="0 0 240 190" fill="none" aria-hidden="true">
        <rect x="30" y="18" width="180" height="154" rx="22" fill="rgba(255,255,255,.12)" stroke="rgba(255,255,255,.55)" stroke-width="2"/>
        <rect x="52" y="42" width="84" height="10" rx="5" fill="rgba(255,255,255,.85)"/>
        <rect x="52" y="62" width="56" height="7" rx="3.5" fill="rgba(255,255,255,.5)"/>
        <g fill="#fff"><rect x="52" y="94" width="14" height="14"/><rect x="70" y="94" width="14" height="14"/><rect x="88" y="104" width="14" height="14"/><rect x="52" y="112" width="14" height="14"/><rect x="106" y="94" width="14" height="14"/><rect x="70" y="130" width="14" height="14"/><rect x="106" y="126" width="14" height="14"/></g>
        <circle cx="172" cy="118" r="26" fill="#8fa95a"/><path d="M160 118l9 9 16-18" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <div class="art-copy"><h2><?= e($title) ?></h2><p><?= e($text) ?></p></div>
</div>
<?php
    return (string)ob_get_clean();
}
