<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Beranda: kalender (harian/mingguan/bulanan), statistik ringkas, dan agenda, berbeda per peran.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $u = user();
        $role = $u['role'];

        // Panitia yang belum divalidasi hanya melihat pemberitahuan.
        if ($role === 'penyelenggara' && $u['status'] !== 'aktif') {
            return view('dashboard', ['pendingOrganizer' => true]);
        }

        /* ---------- Tampilan & tanggal ---------- */
        $view = in_array($request->query('view'), ['day', 'week', 'month'], true) ? $request->query('view') : 'week';
        $d = (string)$request->query('d', '');
        $pick = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false) ? date('Y-m-d', strtotime($d)) : date('Y-m-d');
        $today = date('Y-m-d');
        $pickTs = strtotime($pick);
        $monday = date('Y-m-d', strtotime('monday this week', $pickTs));
        $link = fn(string $v, string $dt) => route('dashboard', ['view' => $v, 'd' => $dt]);

        if ($view === 'day') {
            $from = $to = $pick;
            $prev = date('Y-m-d', strtotime('-1 day', $pickTs));
            $next = date('Y-m-d', strtotime('+1 day', $pickTs));
            $calTitle = HARI[(int)date('N', $pickTs)] . ', ' . tgl($pick, true);
        } elseif ($view === 'week') {
            $from = $monday;
            $to = date('Y-m-d', strtotime('+6 day', strtotime($monday)));
            $prev = date('Y-m-d', strtotime('-7 day', $pickTs));
            $next = date('Y-m-d', strtotime('+7 day', $pickTs));
            $calTitle = tgl($from) . ' – ' . tgl($to);
        } else {
            $first = date('Y-m-01', $pickTs);
            $from = date('Y-m-d', strtotime('monday this week', strtotime($first)));
            $last = date('Y-m-t', $pickTs);
            $to = date('Y-m-d', strtotime('sunday this week', strtotime($last)));
            $prev = date('Y-m-d', strtotime('-1 month', strtotime($first)));
            $next = date('Y-m-d', strtotime('+1 month', strtotime($first)));
            $calTitle = BULAN[(int)date('n', $pickTs)] . ' ' . date('Y', $pickTs);
        }

        /* ---------- Acara pada rentang + penanda per peran ---------- */
        $q = Event::withStats()->whereBetween('event_date', [$from, $to]);
        if ($role === 'penyelenggara') {
            $q->where(fn($w) => $w->where('status', '<>', 'draft')->orWhere('organizer_id', $u['id']));
        } elseif ($role !== 'admin') {
            $q->where('status', '<>', 'draft');
        }
        $events = $q->orderBy('event_date')->orderBy('start_time')->orderBy('id')->get();

        $myReg = [];
        if ($role === 'peserta') {
            foreach (Registration::where('user_id', $u['id'])->get() as $row) {
                $myReg[(int)$row['event_id']] = $row;
            }
        }

        /** [kelas, tautan] untuk satu acara sesuai peran. */
        $classify = function ($ev) use ($role, $u, $myReg, $today): array {
            $id = (int)$ev['id'];
            if ($ev['status'] === 'draft') {
                return ['draft', route('org.participants', $id)];
            }
            if ($role === 'peserta') {
                $reg = $myReg[$id] ?? null;
                if ($reg && $reg['status'] !== 'ditolak') {
                    $cls = (string)$ev['event_date'] < $today ? 'done' : ($reg['status'] === 'disetujui' ? '' : 'wait');
                    return [$cls, route('tickets.show', (int)$reg['id'])];
                }
                return [(string)$ev['event_date'] < $today ? 'done' : 'open', route('events.show', $id)];
            }
            $mine = $role === 'penyelenggara' && (int)$ev['organizer_id'] === (int)$u['id'];
            $href = ($mine || $role === 'admin') ? route('org.participants', $id) : route('events.show', $id);
            if ((string)$ev['event_date'] < $today) {
                return ['done', $href];
            }
            return [$mine || $role === 'admin' ? '' : 'open', $href];
        };

        $byDay = [];
        foreach ($events as $ev) {
            $byDay[(string)$ev['event_date']][] = $ev;
        }

        /* ---------- Statistik ringkas di bar atas ---------- */
        $uid = $u['id'];
        if ($role === 'peserta') {
            $stats = [
                [DB::table('registrations as r')->join('events as e', 'e.id', '=', 'r.event_id')
                    ->where('r.user_id', $uid)->where('r.status', 'disetujui')->where('e.event_date', '>=', $today)->count(), 'tiket aktif'],
                [DB::table('registrations')->where('user_id', $uid)->where('status', 'menunggu')->count(), 'menunggu'],
                [DB::table('certificates as ce')->join('registrations as r', 'r.id', '=', 'ce.registration_id')->where('r.user_id', $uid)->count(), 'sertifikat'],
            ];
            $sub = 'Jadwal seminar Anda dan acara yang masih dibuka.';
            $cta = [route('events.index'), 'Cari seminar', 'compass'];
        } elseif ($role === 'penyelenggara') {
            $stats = [
                [DB::table('events')->where('organizer_id', $uid)->where('status', 'terbit')->where('event_date', '>=', $today)->count(), 'acara aktif'],
                [DB::table('registrations as r')->join('events as e', 'e.id', '=', 'r.event_id')->where('e.organizer_id', $uid)->where('r.status', 'menunggu')->count(), 'perlu disetujui'],
                [DB::table('registrations as r')->join('events as e', 'e.id', '=', 'r.event_id')->where('e.organizer_id', $uid)->where('r.status', 'disetujui')->count(), 'peserta'],
            ];
            $sub = 'Acara Anda tampil bersama acara panitia lain, supaya jadwal tidak bentrok.';
            $cta = [route('org.event.create'), 'Buat acara', 'plus'];
        } else {
            $stats = [
                [DB::table('events')->where('status', 'terbit')->where('event_date', '>=', $today)->count(), 'acara aktif'],
                [DB::table('users')->where('status', 'menunggu')->count(), 'menunggu validasi'],
                [DB::table('registrations')->where('status', 'menunggu')->count(), 'pendaftar menunggu'],
            ];
            $sub = 'Seluruh acara dari semua penyelenggara, termasuk draf.';
            $cta = [route('admin.reports'), 'Lihat laporan', 'chart'];
        }

        /* ---------- Agenda samping: [tautan, judul, keterangan, selesai?] ---------- */
        $ag = [];
        if ($role === 'peserta') {
            $all = [];
            foreach (Registration::with('event')->where('user_id', $uid)->where('status', '<>', 'ditolak')->get() as $r) {
                $all[] = ['reg_id' => $r->id, 'title' => $r->event->title, 'event_date' => (string)$r->event->event_date,
                    'start_time' => $r->event->start_time, 'attended' => $r->attended];
            }
            usort($all, fn($a, $b) => [$a['event_date'] < $today, $a['event_date'], $a['start_time']] <=> [$b['event_date'] < $today, $b['event_date'], $b['start_time']]);
            foreach (array_slice($all, 0, 5) as $a) {
                $ag[] = [route('tickets.show', $a['reg_id']), $a['title'], tgl($a['event_date']) . ' · ' . $a['start_time'], $a['attended'] || $a['event_date'] < $today];
            }
            $agendaTitle = 'Seminar saya';
            $agendaAll = route('tickets.index');
        } else {
            $aq = Event::with('organizer')->where('event_date', '>=', $today);
            if ($role !== 'admin') {
                $aq->where(fn($w) => $w->where('status', 'terbit')->orWhere('organizer_id', $uid));
            }
            foreach ($aq->orderBy('event_date')->orderBy('start_time')->limit(5)->get() as $a) {
                $own = $role === 'admin' || (int)$a['organizer_id'] === (int)$uid;
                $ag[] = [$own ? route('org.participants', $a['id']) : route('events.show', $a['id']), $a['title'],
                    tgl($a['event_date']) . ' · ' . $a['start_time'] . ' · ' . $a['organizer_name'], false];
            }
            $agendaTitle = 'Acara terdekat';
            $agendaAll = route('org.events');
        }

        /* ---------- Mini kalender: tanggal yang punya acara ---------- */
        $mFirst = date('Y-m-01', $pickTs);
        $mStart = date('Y-m-d', strtotime('monday this week', strtotime($mFirst)));
        $mEnd = date('Y-m-d', strtotime('sunday this week', strtotime(date('Y-m-t', $pickTs))));
        $mq = DB::table('events')->whereBetween('event_date', [$mStart, $mEnd]);
        if ($role !== 'admin') {
            $mq->where(function ($w) use ($role, $uid) {
                $w->where('status', '<>', 'draft');
                if ($role === 'penyelenggara') {
                    $w->orWhere('organizer_id', $uid);
                }
            });
        }
        $hasEv = array_flip($mq->distinct()->pluck('event_date')->map(fn($x) => (string)$x)->all());

        return view('dashboard', [
            'pendingOrganizer' => false,
            'role' => $role, 'view' => $view, 'pick' => $pick, 'pickTs' => $pickTs, 'today' => $today,
            'from' => $from, 'to' => $to, 'prev' => $prev, 'next' => $next, 'calTitle' => $calTitle,
            'link' => $link, 'classify' => $classify, 'byDay' => $byDay,
            'stats' => $stats, 'sub' => $sub, 'cta' => $cta,
            'ag' => $ag, 'agendaTitle' => $agendaTitle, 'agendaAll' => $agendaAll,
            'mFirst' => $mFirst, 'mStart' => $mStart, 'mEnd' => $mEnd, 'hasEv' => $hasEv,
            'rangeFrom' => $view === 'month' ? '' : $from, 'rangeTo' => $view === 'month' ? '' : $to,
        ]);
    }
}
