<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/** Cari acara, detail acara, dan pendaftaran peserta. */
class EventController extends Controller
{
    /** Daftar acara terbit dengan pencarian dan filter (tampilan daftar atau galeri poster). */
    public function index()
    {
        $r = request();
        $u = user();
        $q = trim((string)$r->query('q', ''));
        $cat = (string)$r->query('kategori', '');
        $when = (string)$r->query('waktu', '');
        $mode = (string)$r->query('mode', '');
        $fee = (string)$r->query('biaya', '');
        $quota = (string)$r->query('kuota', '');
        $cert = (string)$r->query('sertifikat', '');
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$r->query('date', '')) ? (string)$r->query('date') : '';
        $today = date('Y-m-d');

        $query = Event::withStats()->where('status', 'terbit');
        if ($date) {
            $query->where('event_date', $date);
        } else {
            $query->where('event_date', '>=', $today);          // hanya acara yang masih dibuka
        }
        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(function ($w) use ($like) {
                $w->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('location', 'like', $like)
                    ->orWhereHas('organizer', fn($o) => $o->where('name', 'like', $like));
            });
        }
        if (in_array($cat, CATEGORIES, true)) $query->where('category', $cat);
        if ($when === 'hari') $query->where('event_date', $today);
        if ($when === 'minggu') $query->whereBetween('event_date', [$today, date('Y-m-d', strtotime('+7 days'))]);
        if ($when === 'bulan') $query->where('event_date', 'like', date('Y-m') . '%');
        if (in_array($mode, ['online', 'offline'], true)) $query->where('mode', $mode);
        if ($fee === 'gratis') $query->where('fee', 0);
        if ($fee === 'berbayar') $query->where('fee', '>', 0);
        if ($cert === '1') $query->where('has_certificate', 1);

        $rows = $query->orderBy('event_date')->orderBy('start_time')->get();
        if ($quota === 'tersedia') {
            $rows = $rows->filter(fn($e) => seats_left($e) > 0)->values();
        }
        $filtered = (bool)($q . $cat . $when . $mode . $fee . $quota . $cert . $date);
        $grid = $r->query('tampilan') === 'galeri';
        $tv = fn(string $v) => '?' . http_build_query(array_merge($r->query(), ['tampilan' => $v]));

        return view('pages.events.index', compact('u', 'rows', 'q', 'cat', 'when', 'mode', 'fee', 'quota', 'cert', 'date', 'filtered', 'grid', 'tv'));
    }

    /** Detail satu acara. Draf hanya terlihat oleh pemilik dan admin. */
    public function show(int $id)
    {
        $u = user();
        $ev = get_event($id);
        $isOwner = $ev && ($u['role'] === 'admin' || (int)$ev['organizer_id'] === (int)$u['id']);
        if (!$ev || ($ev['status'] === 'draft' && !$isOwner)) {
            flash('error', 'Acara tidak ditemukan.');
            return redirect()->route('events.index');
        }

        $myReg = $u['role'] === 'peserta'
            ? Registration::where('event_id', $ev['id'])->where('user_id', $u['id'])->first()
            : null;

        // Bentrok jadwal peserta (acara lain pada tanggal yang sama yang tidak ditolak)
        $clash = [];
        if ($u['role'] === 'peserta' && !$myReg) {
            $clash = DB::table('registrations as r')->join('events as e', 'e.id', '=', 'r.event_id')
                ->where('r.user_id', $u['id'])->where('r.status', '<>', 'ditolak')->where('e.event_date', $ev['event_date'])
                ->get(['e.title', 'e.start_time'])->map(fn($x) => (array)$x)->all();
        }

        [$open, $why] = registration_open($ev);
        $left = seats_left($ev);
        $pct = (int)$ev['quota'] > 0 ? min(100, (int)round($ev['registered'] / $ev['quota'] * 100)) : 0;
        $paid = (int)$ev['fee'] > 0;
        $tone = ((int)$ev['id'] % 2) ? 'maroon' : 'army';
        $t = strtotime((string)$ev['event_date']);
        $active = $u['role'] === 'peserta' ? 'events' : ($u['role'] === 'admin' ? 'org_events' : 'org_browse');

        return view('pages.events.show', compact('u', 'ev', 'isOwner', 'myReg', 'clash', 'open', 'why', 'left', 'pct', 'paid', 'tone', 't', 'active'));
    }

    /** Proses pendaftaran peserta (kuota dicek ulang di dalam transaksi). */
    public function register(int $id)
    {
        $u = user();
        $ev = get_event($id);
        $isOwner = $ev && ($u['role'] === 'admin' || (int)$ev['organizer_id'] === (int)$u['id']);
        if (!$ev || ($ev['status'] === 'draft' && !$isOwner)) {
            flash('error', 'Acara tidak ditemukan.');
            return redirect()->route('events.index');
        }
        if ($u['role'] !== 'peserta') {
            flash('error', 'Hanya akun peserta yang dapat mendaftar.');
            return redirect()->route('events.show', $ev['id']);
        }

        try {
            $rid = DB::transaction(function () use ($ev, $u) {
                $fresh = get_event((int)$ev['id']);
                [$open, $why] = registration_open($fresh);
                if (Registration::where('event_id', $ev['id'])->where('user_id', $u['id'])->exists()) {
                    flash('warn', 'Anda sudah terdaftar pada acara ini.');
                    return null;
                }
                if (!$open) {
                    flash('error', $why . '.');
                    return null;
                }
                $paid = (int)$fresh['fee'] > 0;
                $auto = !$paid && $fresh['approval_mode'] === 'auto';     // persetujuan otomatis hanya untuk acara gratis
                $reg = Registration::create([
                    'event_id' => $ev['id'],
                    'user_id' => $u['id'],
                    'ticket_code' => ticket_code(),
                    'status' => $auto ? 'disetujui' : 'menunggu',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                flash('ok', $auto ? 'Pendaftaran disetujui. Tiket QR Anda sudah tersedia.'
                    : ($paid ? 'Pendaftaran tercatat. Lakukan pembayaran dan unggah buktinya agar panitia dapat menyetujui.'
                             : 'Pendaftaran tercatat. Tiket QR muncul setelah panitia menyetujui.'));
                return $reg->id;
            });
        } catch (UniqueConstraintViolationException $ex) {
            flash('warn', 'Anda sudah terdaftar pada acara ini.');
            $rid = null;
        }

        return $rid ? redirect()->route('tickets.show', $rid) : redirect()->route('events.show', $ev['id']);
    }
}
